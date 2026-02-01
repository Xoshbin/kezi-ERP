<?php

namespace Jmeryar\Manufacturing\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Product\Models\Product;

/**
 * @property int $id
 * @property string $currency_code
 * @property float $unit_cost
 * @property float $quantity_consumed
 */
class ManufacturingOrderLine extends Model
{
    use HasFactory;

    protected static function newFactory(): \Jmeryar\Manufacturing\Database\Factories\ManufacturingOrderLineFactory
    {
        return \Jmeryar\Manufacturing\Database\Factories\ManufacturingOrderLineFactory::new();
    }

    protected $fillable = [
        'company_id',
        'manufacturing_order_id',
        'product_id',
        'quantity_required',
        'quantity_consumed',
        'unit_cost',
        'currency_code',
        'stock_move_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity_required' => 'decimal:4',
            'quantity_consumed' => 'decimal:4',
            'unit_cost' => \Jmeryar\Foundation\Casts\BaseCurrencyMoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<ManufacturingOrder, static>
     */
    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    /**
     * @return BelongsTo<Product, static>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<StockMove, static>
     */
    public function stockMove(): BelongsTo
    {
        return $this->belongsTo(StockMove::class);
    }
}
