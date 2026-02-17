<?php

namespace Kezi\Pos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Foundation\Casts\MoneyCast;
use Kezi\Product\Models\Product;

class PosOrderLine extends Model
{
    protected $fillable = [
        'pos_order_id',
        'product_id',
        'quantity',
        'unit_price',
        'tax_amount',
        'total_amount',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => MoneyCast::class,
            'tax_amount' => MoneyCast::class,
            'total_amount' => MoneyCast::class,
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PosOrder::class, 'pos_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
