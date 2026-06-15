<?php

namespace Tests\Unit;

use App\Models\AiSemanticCache;
use App\Services\GeoFlow\SemanticCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 语义缓存服务测试
 *
 * 注意：测试环境使用 SQLite（见 phpunit.xml），不含 pgvector，
 * 因此向量相似度检索无法测试。这里覆盖不依赖 pgvector 的逻辑：
 * - token 估算
 * - prompt hash
 * - 精确匹配命中
 * - 命中计数
 * - TTL 过期判断
 * - 统计数据
 * - 无 embedding 模型时的优雅降级
 */
class SemanticCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): SemanticCacheService
    {
        return app(SemanticCacheService::class);
    }

    private function invokePrivate(object $object, string $method, mixed ...$args): mixed
    {
        $ref = new ReflectionMethod($object, $method);
        $ref->setAccessible(true);

        return $ref->invoke($object, ...$args);
    }

    public function test_token_estimation_handles_chinese_and_english(): void
    {
        $service = $this->service();

        // 纯中文：6 个汉字 / 1.5 = 4 tokens
        $chineseTokens = $this->invokePrivate($service, 'estimateTokens', '人工智能内容生');
        $this->assertGreaterThan(0, $chineseTokens);

        // 纯英文：约 4 字符/token
        $englishTokens = $this->invokePrivate($service, 'estimateTokens', 'artificial intelligence');
        $this->assertGreaterThan(0, $englishTokens);

        // 空字符串
        $emptyTokens = $this->invokePrivate($service, 'estimateTokens', '');
        $this->assertSame(0, $emptyTokens);
    }

    public function test_prompt_hash_is_deterministic_and_unique(): void
    {
        $service = $this->service();

        $hash1 = $this->invokePrivate($service, 'hashPrompt', 'same prompt');
        $hash2 = $this->invokePrivate($service, 'hashPrompt', 'same prompt');
        $hash3 = $this->invokePrivate($service, 'hashPrompt', 'different prompt');

        $this->assertSame($hash1, $hash2, '相同 prompt 应产生相同 hash');
        $this->assertNotSame($hash1, $hash3, '不同 prompt 应产生不同 hash');
        $this->assertSame(64, strlen($hash1), 'SHA-256 hash 应为 64 字符');
    }

    public function test_count_vector_dimensions(): void
    {
        $service = $this->service();

        $this->assertSame(3, $this->invokePrivate($service, 'countVectorDimensions', '[0.1,0.2,0.3]'));
        $this->assertSame(1, $this->invokePrivate($service, 'countVectorDimensions', '[0.5]'));
        $this->assertSame(0, $this->invokePrivate($service, 'countVectorDimensions', '[]'));
        $this->assertSame(0, $this->invokePrivate($service, 'countVectorDimensions', ''));
    }

    public function test_exact_match_hit_returns_cached_content(): void
    {
        $prompt = 'What is GEO content engineering?';
        $hash = hash('sha256', $prompt);

        AiSemanticCache::create([
            'prompt_hash' => $hash,
            'prompt_text' => $prompt,
            'prompt_token_count' => 10,
            'response_content' => 'GEO content engineering is...',
            'response_token_count' => 50,
            'ai_model_id' => 1,
            'expires_at' => now()->addDay(),
        ]);

        $result = $this->service()->get($prompt, 1);

        $this->assertTrue($result['hit']);
        $this->assertSame('GEO content engineering is...', $result['content']);
        $this->assertSame(1.0, $result['similarity']);
    }

    public function test_expired_exact_match_is_not_returned(): void
    {
        $prompt = 'expired prompt';
        AiSemanticCache::create([
            'prompt_hash' => hash('sha256', $prompt),
            'prompt_text' => $prompt,
            'response_content' => 'stale content',
            'ai_model_id' => 1,
            'expires_at' => now()->subDay(), // 已过期
        ]);

        // 精确匹配应跳过过期条目；因测试环境无 embedding 模型，向量检索也不会命中
        $result = $this->service()->get($prompt, 1);

        $this->assertFalse($result['hit']);
    }

    public function test_record_hit_increments_counters(): void
    {
        $cache = AiSemanticCache::create([
            'prompt_hash' => hash('sha256', 'x'),
            'prompt_text' => 'x',
            'response_content' => 'y',
            'hit_count' => 0,
            'tokens_saved' => 0,
        ]);

        $this->service()->recordHit($cache->id, 100);

        $cache->refresh();
        $this->assertSame(1, $cache->hit_count);
        $this->assertSame(100, $cache->tokens_saved);
        $this->assertNotNull($cache->last_hit_at);
    }

    public function test_is_expired_logic(): void
    {
        $active = new AiSemanticCache(['expires_at' => now()->addDay()]);
        $expired = new AiSemanticCache(['expires_at' => now()->subDay()]);
        $permanent = new AiSemanticCache(['expires_at' => null]);

        $this->assertFalse($active->isExpired());
        $this->assertTrue($expired->isExpired());
        $this->assertFalse($permanent->isExpired());
    }

    public function test_prune_expired_removes_only_expired_entries(): void
    {
        AiSemanticCache::create([
            'prompt_hash' => hash('sha256', 'a'), 'prompt_text' => 'a',
            'response_content' => 'a', 'expires_at' => now()->subDay(),
        ]);
        AiSemanticCache::create([
            'prompt_hash' => hash('sha256', 'b'), 'prompt_text' => 'b',
            'response_content' => 'b', 'expires_at' => now()->addDay(),
        ]);

        $deleted = $this->service()->pruneExpired();

        $this->assertSame(1, $deleted);
        $this->assertSame(1, AiSemanticCache::count());
    }

    public function test_statistics_aggregation(): void
    {
        AiSemanticCache::create([
            'prompt_hash' => hash('sha256', 'a'), 'prompt_text' => 'a',
            'response_content' => 'a', 'hit_count' => 5, 'tokens_saved' => 500,
        ]);
        AiSemanticCache::create([
            'prompt_hash' => hash('sha256', 'b'), 'prompt_text' => 'b',
            'response_content' => 'b', 'hit_count' => 3, 'tokens_saved' => 300,
        ]);

        $stats = $this->service()->getStatistics();

        $this->assertSame(2, $stats['total_entries']);
        $this->assertSame(8, $stats['total_hits']);
        $this->assertSame(800, $stats['total_tokens_saved']);
        $this->assertSame(4.0, $stats['hit_rate']); // 8 hits / 2 entries
    }

    public function test_empty_prompt_returns_miss(): void
    {
        $result = $this->service()->get('', 1);
        $this->assertFalse($result['hit']);

        $result = $this->service()->get('   ', 1);
        $this->assertFalse($result['hit']);
    }

    public function test_put_with_empty_input_returns_null(): void
    {
        $this->assertNull($this->service()->put('', 'response'));
        $this->assertNull($this->service()->put('prompt', ''));
    }

    public function test_disabled_cache_returns_miss(): void
    {
        config(['geoflow.semantic_cache_enabled' => false]);

        $prompt = 'test prompt';
        AiSemanticCache::create([
            'prompt_hash' => hash('sha256', $prompt),
            'prompt_text' => $prompt,
            'response_content' => 'cached',
            'expires_at' => now()->addDay(),
        ]);

        $result = $this->service()->get($prompt, 1);
        $this->assertFalse($result['hit'], '缓存禁用时应始终返回 miss');
    }
}
