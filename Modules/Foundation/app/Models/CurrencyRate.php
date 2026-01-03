<?php

namespace Modules\Foundation\Models;

use App\Models\Company;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Foundation\Database\Factories\CurrencyRateFactory;

/**
 * Class CurrencyRate
 *
 *
 * @property int $id
 * @property int $currency_id
 * @property float $rate
 * @property Carbon $effective_date
 * @property string|null $source
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Currency $currency
 *
 * @method static \Modules\Foundation\Database\Factories\CurrencyRateFactory factory($count = null, $state = [])
 * @method static Builder<static>|CurrencyRate newModelQuery()
 * @method static Builder<static>|CurrencyRate newQuery()
 * @method static Builder<static>|CurrencyRate query()
 * @method static Builder<static>|CurrencyRate whereCurrencyId($value)
 * @method static Builder<static>|CurrencyRate whereRate($value)
 * @method static Builder<static>|CurrencyRate whereEffectiveDate($value)
 * @method static Builder<static>|CurrencyRate whereSource($value)
 * @method static Builder<static>|CurrencyRate whereCreatedAt($value)
 * @method static Builder<static>|CurrencyRate whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class CurrencyRate extends Model
{
    /** @use HasFactory<CurrencyRateFactory> */
    use HasFactory;

    protected static function newFactory(): \Modules\Foundation\Database\Factories\CurrencyRateFactory
    {
        return \Modules\Foundation\Database\Factories\CurrencyRateFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'currency_rates';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'currency_id',
        'rate',
        'effective_date',
        'source',
        'company_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rate' => 'decimal:10',
        'effective_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the company that this rate belongs to.
     */
    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the currency that this rate belongs to.
     */
    /**
     * @return BelongsTo<Currency, static>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to get the latest rate for a currency on or before a specific date for a specific company.
     *
     * @param  Carbon|string  $date
     */
    public function scopeLatestRateForDate(Builder $query, int $currencyId, $date, int $companyId): Builder
    {
        // Ensure date is a Carbon instance for proper comparison
        if (! $date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        return $query->where('currency_id', $currencyId)
            ->where('company_id', $companyId)
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->limit(1);
    }

    /**
     * Scope to get rates for a specific currency.
     */
    public function scopeForCurrency(Builder $query, int $currencyId): Builder
    {
        return $query->where('currency_id', $currencyId);
    }

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get the exchange rate for a currency on a specific date for a specific company.
     * Returns the most recent rate on or before the given date.
     *
     * @param  Carbon|string  $date
     */
    public static function getRateForDate(int $currencyId, $date, int $companyId): ?float
    {
        $rate = static::latestRateForDate($currencyId, $date, $companyId)->first();

        return $rate ? (float) $rate->rate : null;
    }

    /**
     * Get the latest exchange rate for a currency for a specific company.
     */
    public static function getLatestRate(int $currencyId, int $companyId): ?float
    {
        $rate = static::forCurrency($currencyId)
            ->where('company_id', $companyId)
            ->orderBy('effective_date', 'desc')
            ->first();

        return $rate ? (float) $rate->rate : null;
    }
}
