<?php

namespace App\Models;

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
        'cost_per_unit' => BaseCurrencyMoneyCast::class,
        'purchase_date' => 'date',
    ];

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
