<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommerceVariant extends Model
{
    protected $fillable = [
        'commerce_product_id',
        'sku',
        'external_id',
        'option_1',
        'option_2',
        'option_3',
        'price',
        'currency',
        'inventory_quantity',
        'inventory_policy',
        'weight',
        'weight_unit',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'commerce_product_id' => 'integer',
            'price' => 'decimal:2',
            'inventory_quantity' => 'integer',
            'weight' => 'decimal:3',
            'raw_payload' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(CommerceProduct::class, 'commerce_product_id');
    }
}
