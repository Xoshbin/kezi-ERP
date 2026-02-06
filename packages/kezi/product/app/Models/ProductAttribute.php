<?php

namespace Kezi\Product\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $company_id
 * @property array<array-key, mixed> $name
 * @property string $type
 * @property int $sort_order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read mixed $translations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Product\Models\ProductAttributeValue> $values
 * @property-read int|null $values_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttribute active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttribute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttribute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttribute query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttribute whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttribute whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttribute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttribute whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttribute whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttribute whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttribute whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttribute whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttribute whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttribute whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttribute whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductAttribute whereUpdatedAt($value)
 * @method static \Kezi\Product\Database\Factories\ProductAttributeFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class ProductAttribute extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public array $translatable = ['name'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'product_attribute_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): \Kezi\Product\Database\Factories\ProductAttributeFactory
    {
        return \Kezi\Product\Database\Factories\ProductAttributeFactory::new();
    }
}
