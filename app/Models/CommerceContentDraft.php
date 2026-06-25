<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommerceContentDraft extends Model
{
    protected $fillable = [
        'commerce_product_id',
        'language',
        'channel',
        'title',
        'description',
        'bullets',
        'faq',
        'prompt',
        'generation_mode',
    ];

    protected function casts(): array
    {
        return [
            'commerce_product_id' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(CommerceProduct::class, 'commerce_product_id');
    }
}
