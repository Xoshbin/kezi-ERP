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
