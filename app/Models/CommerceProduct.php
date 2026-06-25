<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommerceProduct extends Model
{
    protected $fillable = [
        'source',
        'external_id',
        'sku',
        'title',
        'description',
        'vendor',
        'product_type',
        'material',
        'origin_country',
        'certifications',
        'faq',
        'support_policy',
        'language',
        'status',
        'raw_payload',
        'knowledge_base_id',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'knowledge_base_id' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(CommerceVariant::class);
    }

    public function inventorySnapshots(): HasMany
    {
        return $this->hasMany(CommerceInventorySnapshot::class);
    }

    public function contentDrafts(): HasMany
    {
        return $this->hasMany(CommerceContentDraft::class);
    }

    public function knowledgeBase(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class);
    }
}
