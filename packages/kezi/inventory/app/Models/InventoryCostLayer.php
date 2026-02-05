<?php

namespace Kezi\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Kezi\Product\Models\Product;

/**
 * @property float $remaining_quantity
 * @property \Brick\Money\Money|null $cost_per_unit
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property float $quantity
 * @property \Illuminate\Support\Carbon $purchase_date
 * @property string $source_type
 * @property int $source_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read Product $product
 * @property-read \Illuminate\Database\Eloquent\Model $source
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCostLayer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCostLayer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCostLayer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCostLayer whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCostLayer whereCostPerUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCostLayer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCostLayer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCostLayer whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCostLayer wherePurchaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCostLayer whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCostLayer whereRemainingQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCostLayer whereSourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCostLayer whereSourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryCostLayer whereUpdatedAt($value)
 * @method static \Kezi\Inventory\Database\Factories\InventoryCostLayerFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class InventoryCostLayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'quantity',
        'cost_per_unit',
        'remaining_quantity',
        'purchase_date',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'quantity' => 'float',
        'remaining_quantity' => 'float',
        'cost_per_unit' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'purchase_date' => 'date',
    ];

    /**
     * The relationships that should always be loaded.
     * Eager-loading the `company.currency` relationship is critical for monetary fields.
     */
    protected $with = ['company.currency', 'product.company.currency'];

    /**
     * @return BelongsTo<\App\Models\Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    /**
     * @return BelongsTo<Product, static>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return MorphTo<Model, static>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): \Kezi\Inventory\Database\Factories\InventoryCostLayerFactory
    {
        return \Kezi\Inventory\Database\Factories\InventoryCostLayerFactory::new();
    }
}
