<?php

namespace Modules\Inventory\Models;

use App\Casts\BaseCurrencyMoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
        'cost_per_unit' => BaseCurrencyMoneyCast::class,
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
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, static>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
