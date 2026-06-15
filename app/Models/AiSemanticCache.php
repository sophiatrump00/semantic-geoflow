<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI 语义缓存模型
 *
 * @property int $id
 * @property string $prompt_hash
 * @property string $prompt_text
 * @property int $prompt_token_count
 * @property string $response_content
 * @property int $response_token_count
 * @property int|null $ai_model_id
 * @property string|null $model_identifier
 * @property int|null $embedding_model_id
 * @property int $embedding_dimensions
 * @property string $embedding_provider
 * @property int $hit_count
 * @property int $tokens_saved
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $last_hit_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AiSemanticCache extends Model
{
    protected $table = 'ai_semantic_cache';

    protected $fillable = [
        'prompt_hash',
        'prompt_text',
        'prompt_token_count',
        'response_content',
        'response_token_count',
        'ai_model_id',
        'model_identifier',
        'embedding_model_id',
        'embedding_dimensions',
        'embedding_provider',
        'hit_count',
        'tokens_saved',
        'expires_at',
        'last_hit_at',
    ];

    protected function casts(): array
    {
        return [
            'prompt_token_count' => 'integer',
            'response_token_count' => 'integer',
            'ai_model_id' => 'integer',
            'embedding_model_id' => 'integer',
            'embedding_dimensions' => 'integer',
            'hit_count' => 'integer',
            'tokens_saved' => 'integer',
            'expires_at' => 'datetime',
            'last_hit_at' => 'datetime',
        ];
    }

    public function aiModel(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'ai_model_id');
    }

    public function embeddingModel(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'embedding_model_id');
    }

    /**
     * 判断缓存是否已过期
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * 记录一次缓存命中
     */
    public function recordHit(int $tokensSaved = 0): void
    {
        $this->increment('hit_count');
        $this->increment('tokens_saved', $tokensSaved);
        $this->update(['last_hit_at' => now()]);
    }
}
