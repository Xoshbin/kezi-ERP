<?php

namespace Kezi\Pos\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Product\Models\Product;

/**
 * @property int $id
 * @property int $pos_return_id
 * @property int $original_order_line_id
 * @property int $product_id
 * @property float $quantity_returned
 * @property float $quantity_available
 * @property \Brick\Money\Money $unit_price
 * @property \Brick\Money\Money $refund_amount
 * @property \Brick\Money\Money $restocking_fee_line
 * @property bool $restock
 * @property string|null $item_condition
 * @property string|null $return_reason_line
 * @property array|null $metadata
 */
class PosReturnLine extends Model
{
    /** @use HasFactory<\Kezi\Pos\Database\Factories\PosReturnLineFactory> */
    use HasFactory;

    protected static function newFactory(): \Kezi\Pos\Database\Factories\PosReturnLineFactory
    {
        return \Kezi\Pos\Database\Factories\PosReturnLineFactory::new();
    }

    protected $fillable = [
        'pos_return_id',
        'original_order_line_id',
        'product_id',
        'quantity_returned',
        'quantity_available',
        'unit_price',
        'refund_amount',
        'restocking_fee_line',
        'restock',
        'item_condition',
        'return_reason_line',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity_returned' => 'decimal:4',
            'quantity_available' => 'decimal:4',
            'unit_price' => \Kezi\Pos\Casts\PosOrderLineMoneyCast::class,
            'refund_amount' => \Kezi\Pos\Casts\PosOrderLineMoneyCast::class,
            'restocking_fee_line' => \Kezi\Pos\Casts\PosOrderLineMoneyCast::class,
            'restock' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function posReturn(): BelongsTo
    {
        return $this->belongsTo(PosReturn::class);
    }

    public function originalOrderLine(): BelongsTo
    {
        return $this->belongsTo(PosOrderLine::class, 'original_order_line_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
