<?php

namespace Jmeryar\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $product_attribute_id
 * @property string $name
 */
class ProductAttributeValue extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'product_attribute_id',
        'name',
        'color_code',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public array $translatable = ['name'];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::deleting(function (ProductAttributeValue $value) {
            if (ProductVariantAttribute::where('product_attribute_value_id', $value->id)->exists()) {
                throw new \RuntimeException(
                    "Cannot delete attribute value '{$value->name}' because it is being used by one or more product variants."
                );
            }
        });
    }

    protected static function newFactory(): \Jmeryar\Product\Database\Factories\ProductAttributeValueFactory
    {
        return \Jmeryar\Product\Database\Factories\ProductAttributeValueFactory::new();
    }
}
