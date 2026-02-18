<?php

namespace Kezi\Pos\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Product\Models\Product;

/**
 * @property int $id
 * @property int $pos_order_id
 * @property int $product_id
 * @property float $quantity
 * @property \Brick\Money\Money $unit_price
 * @property \Brick\Money\Money $discount_amount
 * @property \Brick\Money\Money $tax_amount
 * @property \Brick\Money\Money $total_amount
 * @property array|null $metadata
 * @property-read \Kezi\Pos\Models\PosOrder $order
 * @property-read \Kezi\Product\Models\Product|null $product
 */
class PosOrderLine extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Kezi\Pos\Database\Factories\PosOrderLineFactory::new();
    }

    protected $fillable = [
        'pos_order_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => \Kezi\Pos\Casts\PosOrderLineMoneyCast::class,
            'discount_amount' => \Kezi\Pos\Casts\PosOrderLineMoneyCast::class,
            'tax_amount' => \Kezi\Pos\Casts\PosOrderLineMoneyCast::class,
            'total_amount' => \Kezi\Pos\Casts\PosOrderLineMoneyCast::class,
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
