<?php

namespace Kezi\Manufacturing\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property array<array-key, mixed> $name
 * @property \Brick\Money\Money $hourly_cost
 * @property string $currency_code
 * @property numeric $capacity
 * @property bool $is_active
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Manufacturing\Models\BOMLine> $bomLines
 * @property-read int|null $bom_lines_count
 * @property-read Company $company
 * @property-read mixed $translations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Manufacturing\Models\WorkOrder> $workOrders
 * @property-read int|null $work_orders_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter whereCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter whereCurrencyCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter whereHourlyCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkCenter whereUpdatedAt($value)
 * @method static \Kezi\Manufacturing\Database\Factories\WorkCenterFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class WorkCenter extends Model
{
    use HasFactory;
    use HasTranslations;

    protected static function newFactory(): \Kezi\Manufacturing\Database\Factories\WorkCenterFactory
    {
        return \Kezi\Manufacturing\Database\Factories\WorkCenterFactory::new();
    }

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'hourly_cost',
        'currency_code',
        'capacity',
        'is_active',
        'notes',
    ];

    public array $translatable = ['name'];

    protected $casts = [
        'capacity' => 'decimal:2',
        'hourly_cost' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<BOMLine, static>
     */
    public function bomLines(): HasMany
    {
        return $this->hasMany(BOMLine::class);
    }

    /**
     * @return HasMany<WorkOrder, static>
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
