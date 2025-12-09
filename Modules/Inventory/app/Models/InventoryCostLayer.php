<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Foundation\Casts\BaseCurrencyMoneyCast;
use Modules\Product\Models\Product;

class InventoryCostLayer extends Model
{
    use HasFactory;

    protected $fillable = [
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
        'cost_per_unit' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'purchase_date' => 'date',
    ];

    /**
     * The relationships that should always be loaded.
     * Eager-loading the `product.company.currency` relationship is critical because the `BaseCurrencyMoneyCast`
     * for monetary fields on this model depends on the currency context provided by the product's company.
     *
     * @var list<string>
     */
    protected $with = ['product.company.currency'];

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

    protected static function newFactory(): \Modules\Inventory\Database\Factories\InventoryCostLayerFactory
    {
        return \Modules\Inventory\Database\Factories\InventoryCostLayerFactory::new();
    }
}
