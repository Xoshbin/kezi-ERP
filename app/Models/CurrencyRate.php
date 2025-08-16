<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\CurrencyRateFactory;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class CurrencyRate
 *
 * @package App\Models
 *
 * This Eloquent model represents historical exchange rates for currencies.
 * It maintains a complete history of exchange rate changes over time,
 * which is essential for accurate multi-currency accounting and reporting.
 *
 * @property int $id
 * @property int $currency_id
 * @property float $rate
 * @property Carbon $effective_date
 * @property string|null $source
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Currency $currency
 * @method static CurrencyRateFactory factory($count = null, $state = [])
 * @method static Builder<static>|CurrencyRate newModelQuery()
 * @method static Builder<static>|CurrencyRate newQuery()
 * @method static Builder<static>|CurrencyRate query()
 * @method static Builder<static>|CurrencyRate whereCurrencyId($value)
 * @method static Builder<static>|CurrencyRate whereRate($value)
 * @method static Builder<static>|CurrencyRate whereEffectiveDate($value)
 * @method static Builder<static>|CurrencyRate whereSource($value)
 * @method static Builder<static>|CurrencyRate whereCreatedAt($value)
 * @method static Builder<static>|CurrencyRate whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CurrencyRate extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'currency_rates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
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
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the currency that this rate belongs to.
     *
     * @return BelongsTo
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
     * Scope to get the latest rate for a currency on or before a specific date.
     *
     * @param Builder $query
     * @param int $currencyId
     * @param Carbon|string $date
     * @return Builder
     */
    public function scopeLatestRateForDate(Builder $query, int $currencyId, $date): Builder
    {
        return $query->where('currency_id', $currencyId)
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->limit(1);
    }

    /**
     * Scope to get rates for a specific currency.
     *
     * @param Builder $query
     * @param int $currencyId
     * @return Builder
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
     * Get the exchange rate for a currency on a specific date.
     * Returns the most recent rate on or before the given date.
     *
     * @param int $currencyId
     * @param Carbon|string $date
     * @return float|null
     */
    public static function getRateForDate(int $currencyId, $date): ?float
    {
        $rate = static::latestRateForDate($currencyId, $date)->first();

        return $rate?->rate;
    }

    /**
     * Get the latest exchange rate for a currency.
     *
     * @param int $currencyId
     * @return float|null
     */
    public static function getLatestRate(int $currencyId): ?float
    {
        $rate = static::forCurrency($currencyId)
            ->orderBy('effective_date', 'desc')
            ->first();

        return $rate?->rate;
    }
}
