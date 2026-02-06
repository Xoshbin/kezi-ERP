<?php

namespace Kezi\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $product_attribute_id
 * @property int $product_attribute_value_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Kezi\Product\Models\ProductAttribute $attribute
 * @property-read \Kezi\Product\Models\Product $product
 * @property-read \Kezi\Product\Models\ProductAttributeValue $value
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantAttribute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantAttribute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantAttribute query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantAttribute whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantAttribute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantAttribute whereProductAttributeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantAttribute whereProductAttributeValueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantAttribute whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantAttribute whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ProductVariantAttribute extends Model
{
    protected $fillable = [
        'product_id',
        'product_attribute_id',
        'product_attribute_value_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    public function value(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeValue::class, 'product_attribute_value_id');
    }
}
