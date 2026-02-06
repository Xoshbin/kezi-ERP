<?php

namespace Kezi\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $product_attribute_id
 * @property string $name
 * @property string|null $color_code
 * @property int $sort_order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Kezi\Product\Models\ProductAttribute $attribute
 * @property-read mixed $translations
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttributeValue active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttributeValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttributeValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttributeValue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttributeValue whereColorCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttributeValue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttributeValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttributeValue whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttributeValue whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttributeValue whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttributeValue whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttributeValue whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttributeValue whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttributeValue whereProductAttributeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttributeValue whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttributeValue whereUpdatedAt($value)
 * @method static \Kezi\Product\Database\Factories\ProductAttributeValueFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
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

    protected static function newFactory(): \Kezi\Product\Database\Factories\ProductAttributeValueFactory
    {
        return \Kezi\Product\Database\Factories\ProductAttributeValueFactory::new();
    }
}
