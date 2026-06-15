<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 语义缓存表：基于 prompt embedding 相似度检索，降低重复 AI 调用成本。
 *
 * 设计要点：
 * - prompt_hash: 快速精确匹配（SHA-256）
 * - prompt_embedding: pgvector 向量列，用于相似度检索（余弦距离 <=>）
 * - response_*: 缓存的生成结果与元数据
 * - hit_count/tokens_saved: 命中统计，用于缓存价值评估
 * - expires_at: TTL 失效时间
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_semantic_cache', function (Blueprint $table) {
            $table->id();

            // Prompt 标识与内容
            $table->string('prompt_hash', 64)->index()->comment('Prompt SHA-256 hash，用于精确匹配');
            $table->text('prompt_text')->comment('原始 prompt 文本（用于调试和展示）');
            $table->unsignedInteger('prompt_token_count')->default(0)->comment('Prompt token 估算值');

            // 缓存的响应
            $table->text('response_content')->comment('缓存的生成结果');
            $table->unsignedInteger('response_token_count')->default(0)->comment('响应 token 估算值');

            // 模型信息
            $table->unsignedBigInteger('ai_model_id')->nullable()->index()->comment('生成时使用的模型 ID');
            $table->string('model_identifier', 100)->nullable()->comment('模型标识（model_id）');

            // Embedding 元数据
            $table->unsignedBigInteger('embedding_model_id')->nullable()->comment('生成 embedding 的模型 ID');
            $table->unsignedSmallInteger('embedding_dimensions')->default(0)->comment('向量维度');
            $table->string('embedding_provider', 50)->default('')->comment('Embedding provider');

            // 缓存统计
            $table->unsignedInteger('hit_count')->default(0)->index()->comment('缓存命中次数');
            $table->unsignedInteger('tokens_saved')->default(0)->comment('累计节省的 token 数');

            // 失效控制
            $table->timestamp('expires_at')->nullable()->index()->comment('缓存过期时间（TTL）');
            $table->timestamp('last_hit_at')->nullable()->comment('最后命中时间');

            $table->timestamps();

            // 复合索引：按模型和过期时间查询
            $table->index(['ai_model_id', 'expires_at']);
        });

        // 添加 pgvector 列（仅在 PostgreSQL 且已安装 vector 扩展时）
        if (DB::getDriverName() === 'pgsql') {
            $hasVector = DB::selectOne("
                SELECT EXISTS (
                    SELECT 1 FROM pg_extension WHERE extname = 'vector'
                ) as ok
            ");

            if ($hasVector && $hasVector->ok) {
                // 向量列维度对齐项目统一存储维度（vector(3072)，见 knowledge_chunks）。
                // generateQueryVectorLiteral() 会把任意 embedding 模型的输出 pad/截断到 3072，
                // 故此处必须一致，否则写入时维度不匹配。
                //
                // 注意：pgvector 0.8.x 的 HNSW/IVFFlat 索引维度上限约 2000，3072 维无法建 ANN 索引，
                // 因此这里只存列、走精确余弦距离扫描。语义缓存表通常远小于知识库切片量
                // （受 max_entries LRU 限制），全表扫描在万级数据下仍可接受；
                // 若未来改用 ≤2000 维 embedding，可在此补建 HNSW 索引进一步加速。
                DB::statement('ALTER TABLE ai_semantic_cache ADD COLUMN prompt_embedding vector(3072)');
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_semantic_cache');
    }
};
