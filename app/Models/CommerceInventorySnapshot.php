<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommerceInventorySnapshot extends Model
{
    protected $fillable = [
        'commerce_product_id',
        'commerce_variant_id',
        'quantity',
        'location_name',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'commerce_product_id' => 'integer',
            'commerce_variant_id' => 'integer',
            'quantity' => 'integer',
            'captured_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(CommerceProduct::class, 'commerce_product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(CommerceVariant::class, 'commerce_variant_id');
    }
}
