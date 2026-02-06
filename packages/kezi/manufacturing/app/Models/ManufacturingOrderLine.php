<?php

namespace Kezi\Manufacturing\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Inventory\Models\StockMove;
use Kezi\Product\Models\Product;

/**
 * @property int $id
 * @property string $currency_code
 * @property float $unit_cost
 * @property float $quantity_consumed
 * @property int $company_id
 * @property int $manufacturing_order_id
 * @property int $product_id
 * @property numeric $quantity_required
 * @property int|null $stock_move_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read \Kezi\Manufacturing\Models\ManufacturingOrder $manufacturingOrder
 * @property-read Product $product
 * @property-read StockMove|null $stockMove
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManufacturingOrderLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManufacturingOrderLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManufacturingOrderLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManufacturingOrderLine whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManufacturingOrderLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManufacturingOrderLine whereCurrencyCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManufacturingOrderLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManufacturingOrderLine whereManufacturingOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManufacturingOrderLine whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManufacturingOrderLine whereQuantityConsumed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManufacturingOrderLine whereQuantityRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManufacturingOrderLine whereStockMoveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManufacturingOrderLine whereUnitCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManufacturingOrderLine whereUpdatedAt($value)
 * @method static \Kezi\Manufacturing\Database\Factories\ManufacturingOrderLineFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class ManufacturingOrderLine extends Model
{
    use HasFactory;

    protected static function newFactory(): \Kezi\Manufacturing\Database\Factories\ManufacturingOrderLineFactory
    {
        return \Kezi\Manufacturing\Database\Factories\ManufacturingOrderLineFactory::new();
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
            'unit_cost' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
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
