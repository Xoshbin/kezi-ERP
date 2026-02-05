<?php

namespace Kezi\Manufacturing\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Manufacturing\Enums\BOMType;
use Kezi\Product\Models\Product;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property string $code
 * @property string $name
 * @property \Kezi\Manufacturing\Enums\BOMType $type
 * @property float $quantity
 * @property bool $is_active
 * @property string|null $notes
 * @property-read \App\Models\Company $company
 * @property-read \Kezi\Product\Models\Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection|\Kezi\Manufacturing\Models\BOMLine[] $lines
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read int|null $lines_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Manufacturing\Models\ManufacturingOrder> $manufacturingOrders
 * @property-read int|null $manufacturing_orders_count
 * @property-read mixed $translations
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillOfMaterial whereUpdatedAt($value)
 * @method static \Kezi\Manufacturing\Database\Factories\BillOfMaterialFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class BillOfMaterial extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $table = 'bills_of_materials';

    protected static function newFactory(): \Kezi\Manufacturing\Database\Factories\BillOfMaterialFactory
    {
        return \Kezi\Manufacturing\Database\Factories\BillOfMaterialFactory::new();
    }

    protected $fillable = [
        'company_id',
        'product_id',
        'code',
        'name',
        'type',
        'quantity',
        'is_active',
        'notes',
    ];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'type' => BOMType::class,
            'quantity' => 'decimal:4',
            'is_active' => 'boolean',
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
     * @return BelongsTo<Product, static>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<BOMLine, static>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(BOMLine::class, 'bom_id');
    }

    /**
     * @return HasMany<ManufacturingOrder, static>
     */
    public function manufacturingOrders(): HasMany
    {
        return $this->hasMany(ManufacturingOrder::class, 'bom_id');
    }
}
