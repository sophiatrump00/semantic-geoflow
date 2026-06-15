<?php

namespace App\Services\GeoFlow;

use App\Models\AiSemanticCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AI 语义缓存服务
 *
 * 基于 prompt embedding 相似度检索，降低重复或相似请求的 AI 调用成本。
 *
 * 核心流程：
 * 1. get() - 查询缓存：先精确匹配 hash，未命中则向量相似度检索
 * 2. put() - 写入缓存：生成 embedding 并存储
 * 3. recordHit() - 命中计数：更新统计指标
 *
 * 配置项（config/geoflow.php）：
 * - semantic_cache_enabled: 是否启用缓存
 * - semantic_cache_similarity_threshold: 相似度阈值（0.0-1.0，推荐 0.92）
 * - semantic_cache_ttl_seconds: 缓存有效期（秒）
 * - semantic_cache_max_entries: 最大缓存条目数（LRU）
 */
class SemanticCacheService
{
    public function __construct(
        private readonly KnowledgeChunkSyncService $knowledgeChunkSyncService
    ) {}

    /**
     * 查询缓存
     *
     * @param  string  $prompt  待查询的 prompt 文本
     * @param  int|null  $aiModelId  模型 ID（可选，用于模型隔离）
     * @return array{hit: bool, content: string|null, cache_id: int|null, similarity: float|null}
     */
    public function get(string $prompt, ?int $aiModelId = null): array
    {
        if (! $this->isEnabled()) {
            return ['hit' => false, 'content' => null, 'cache_id' => null, 'similarity' => null];
        }

        $prompt = trim($prompt);
        if ($prompt === '') {
            return ['hit' => false, 'content' => null, 'cache_id' => null, 'similarity' => null];
        }

        // 1. 精确匹配（hash）
        $hash = $this->hashPrompt($prompt);
        $exactMatch = $this->findExactMatch($hash, $aiModelId);
        if ($exactMatch !== null) {
            return [
                'hit' => true,
                'content' => $exactMatch->response_content,
                'cache_id' => $exactMatch->id,
                'similarity' => 1.0,
            ];
        }

        // 2. 语义相似度匹配（向量）
        try {
            $vectorLiteral = $this->knowledgeChunkSyncService->generateQueryVectorLiteral($prompt);
            if ($vectorLiteral === '') {
                return ['hit' => false, 'content' => null, 'cache_id' => null, 'similarity' => null];
            }

            $similarMatch = $this->findSimilarMatch($vectorLiteral, $aiModelId);
            if ($similarMatch !== null) {
                return [
                    'hit' => true,
                    'content' => $similarMatch['content'],
                    'cache_id' => $similarMatch['id'],
                    'similarity' => $similarMatch['similarity'],
                ];
            }
        } catch (Throwable $e) {
            Log::warning('Semantic cache embedding generation failed', [
                'error' => $e->getMessage(),
                'prompt_length' => mb_strlen($prompt),
            ]);
        }

        return ['hit' => false, 'content' => null, 'cache_id' => null, 'similarity' => null];
    }

    /**
     * 写入缓存
     *
     * @param  string  $prompt  原始 prompt
     * @param  string  $response  生成的响应
     * @param  int|null  $aiModelId  生成时使用的模型 ID
     * @param  string|null  $modelIdentifier  模型标识
     * @param  int  $promptTokenCount  Prompt token 数
     * @param  int  $responseTokenCount  响应 token 数
     */
    public function put(
        string $prompt,
        string $response,
        ?int $aiModelId = null,
        ?string $modelIdentifier = null,
        int $promptTokenCount = 0,
        int $responseTokenCount = 0
    ): ?int {
        if (! $this->isEnabled()) {
            return null;
        }

        $prompt = trim($prompt);
        $response = trim($response);
        if ($prompt === '' || $response === '') {
            return null;
        }

        try {
            // 复用知识库的 embedding 逻辑生成 pgvector 字面量
            $vectorLiteral = $this->knowledgeChunkSyncService->generateQueryVectorLiteral($prompt);
            if ($vectorLiteral === '') {
                // 无可用 embedding 模型或调用失败时，跳过缓存写入（不影响主流程）
                return null;
            }

            // LRU: 检查缓存容量，超出则删除最旧的
            $this->enforceMaxEntries();

            $cache = AiSemanticCache::create([
                'prompt_hash' => $this->hashPrompt($prompt),
                'prompt_text' => mb_substr($prompt, 0, 10000), // 限制存储长度
                'prompt_token_count' => $promptTokenCount > 0 ? $promptTokenCount : $this->estimateTokens($prompt),
                'response_content' => $response,
                'response_token_count' => $responseTokenCount > 0 ? $responseTokenCount : $this->estimateTokens($response),
                'ai_model_id' => $aiModelId,
                'model_identifier' => $modelIdentifier,
                'embedding_dimensions' => $this->countVectorDimensions($vectorLiteral),
                'expires_at' => now()->addSeconds($this->getTtl()),
            ]);

            // 写入 pgvector 列
            DB::statement(
                'UPDATE ai_semantic_cache SET prompt_embedding = ?::vector WHERE id = ?',
                [$vectorLiteral, $cache->id]
            );

            return $cache->id;
        } catch (Throwable $e) {
            Log::error('Failed to write semantic cache', [
                'error' => $e->getMessage(),
                'prompt_length' => mb_strlen($prompt),
            ]);

            return null;
        }
    }

    /**
     * 记录缓存命中
     */
    public function recordHit(int $cacheId, int $tokensSaved = 0): void
    {
        try {
            $cache = AiSemanticCache::find($cacheId);
            if ($cache === null) {
                return;
            }

            $cache->recordHit($tokensSaved);
        } catch (Throwable $e) {
            Log::warning('Failed to record cache hit', [
                'cache_id' => $cacheId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 清理过期缓存
     */
    public function pruneExpired(): int
    {
        return AiSemanticCache::query()
            ->where('expires_at', '<', now())
            ->delete();
    }

    /**
     * 获取缓存统计
     *
     * @return array{total_entries: int, total_hits: int, total_tokens_saved: int, hit_rate: float}
     */
    public function getStatistics(): array
    {
        $total = AiSemanticCache::query()->count();
        $totalHits = AiSemanticCache::query()->sum('hit_count');
        $totalTokensSaved = AiSemanticCache::query()->sum('tokens_saved');

        $hitRate = $total > 0 ? ($totalHits / $total) : 0.0;

        return [
            'total_entries' => (int) $total,
            'total_hits' => (int) $totalHits,
            'total_tokens_saved' => (int) $totalTokensSaved,
            'hit_rate' => round($hitRate, 4),
        ];
    }

    /**
     * 精确匹配查询
     */
    private function findExactMatch(string $hash, ?int $aiModelId): ?AiSemanticCache
    {
        $query = AiSemanticCache::query()
            ->where('prompt_hash', $hash)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        if ($aiModelId !== null) {
            $query->where('ai_model_id', $aiModelId);
        }

        return $query->first();
    }

    /**
     * 相似度匹配查询
     *
     * @param  string  $vectorLiteral  pgvector 字面量字符串
     * @return array{id: int, content: string, similarity: float}|null
     */
    private function findSimilarMatch(string $vectorLiteral, ?int $aiModelId): ?array
    {
        $threshold = $this->getSimilarityThreshold();

        // pgvector 余弦相似度查询: 1 - (a <=> b) = similarity
        // <=> 是余弦距离操作符，范围 [0, 2]，0 表示完全相同
        $sql = "
            SELECT id, response_content, (1 - (prompt_embedding <=> ?::vector)) as similarity
            FROM ai_semantic_cache
            WHERE prompt_embedding IS NOT NULL
              AND (expires_at IS NULL OR expires_at > NOW())
        ";

        $bindings = [$vectorLiteral];

        if ($aiModelId !== null) {
            $sql .= ' AND ai_model_id = ?';
            $bindings[] = $aiModelId;
        }

        $sql .= ' AND (1 - (prompt_embedding <=> ?::vector)) >= ?';
        $bindings[] = $vectorLiteral;
        $bindings[] = $threshold;

        $sql .= ' ORDER BY prompt_embedding <=> ?::vector ASC LIMIT 1';
        $bindings[] = $vectorLiteral;

        try {
            $result = DB::selectOne($sql, $bindings);
            if ($result === null) {
                return null;
            }

            return [
                'id' => (int) $result->id,
                'content' => (string) $result->response_content,
                'similarity' => (float) $result->similarity,
            ];
        } catch (Throwable $e) {
            Log::warning('Semantic similarity search failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 统计 pgvector 字面量的维度数（用于记录）
     *
     * 字面量形如 "[0.1,0.2,...]"，维度即逗号分隔的元素个数。
     */
    private function countVectorDimensions(string $vectorLiteral): int
    {
        $trimmed = trim($vectorLiteral, "[] \t\n\r");
        if ($trimmed === '') {
            return 0;
        }

        return substr_count($trimmed, ',') + 1;
    }

    private function enforceMaxEntries(): void
    {
        $maxEntries = $this->getMaxEntries();
        if ($maxEntries <= 0) {
            return;
        }

        $currentCount = AiSemanticCache::query()->count();
        if ($currentCount < $maxEntries) {
            return;
        }

        // LRU: 删除最久未命中的条目
        $toDelete = $currentCount - $maxEntries + 1;
        $oldestIds = AiSemanticCache::query()
            ->orderBy('last_hit_at')
            ->orderBy('created_at')
            ->limit($toDelete)
            ->pluck('id');

        AiSemanticCache::query()->whereIn('id', $oldestIds)->delete();
    }

    private function hashPrompt(string $prompt): string
    {
        return hash('sha256', $prompt);
    }

    private function estimateTokens(string $text): int
    {
        // 简单估算：中文 ~1.5 字符/token，英文 ~4 字符/token
        $len = mb_strlen($text);
        $chineseCount = preg_match_all('/[\x{4e00}-\x{9fa5}]/u', $text);
        $nonChineseCount = $len - $chineseCount;

        return (int) ceil($chineseCount / 1.5 + $nonChineseCount / 4);
    }

    private function isEnabled(): bool
    {
        return (bool) config('geoflow.semantic_cache_enabled', true);
    }

    private function getSimilarityThreshold(): float
    {
        return (float) config('geoflow.semantic_cache_similarity_threshold', 0.92);
    }

    private function getTtl(): int
    {
        return (int) config('geoflow.semantic_cache_ttl_seconds', 86400 * 7); // 默认 7 天
    }

    private function getMaxEntries(): int
    {
        return (int) config('geoflow.semantic_cache_max_entries', 10000);
    }
}
