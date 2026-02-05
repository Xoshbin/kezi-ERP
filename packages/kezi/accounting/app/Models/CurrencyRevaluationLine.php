<?php

namespace Kezi\Accounting\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Foundation\Casts\BaseCurrencyMoneyCast;
use Kezi\Foundation\Casts\OriginalCurrencyMoneyCast;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;

/**
 * Currency Revaluation Line Model
 *
 * Represents a single account/currency combination being revalued.
 * Tracks the historical rate, current rate, and resulting adjustment.
 *
 * @property int $id
 * @property int $currency_revaluation_id
 * @property int $account_id
 * @property int $currency_id
 * @property int|null $partner_id
 * @property Money $foreign_currency_balance
 * @property float $historical_rate
 * @property float $current_rate
 * @property Money $book_value
 * @property Money $revalued_amount
 * @property Money $adjustment_amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read CurrencyRevaluation $currencyRevaluation
 * @property-read Account $account
 * @property-read Currency $currency
 * @property-read Partner|null $partner
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyRevaluationLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyRevaluationLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyRevaluationLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyRevaluationLine whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyRevaluationLine whereAdjustmentAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyRevaluationLine whereBookValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyRevaluationLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyRevaluationLine whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyRevaluationLine whereCurrencyRevaluationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyRevaluationLine whereCurrentRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyRevaluationLine whereForeignCurrencyBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyRevaluationLine whereHistoricalRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyRevaluationLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyRevaluationLine wherePartnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyRevaluationLine whereRevaluedAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurrencyRevaluationLine whereUpdatedAt($value)
 * @method static \Kezi\Accounting\Database\Factories\CurrencyRevaluationLineFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class CurrencyRevaluationLine extends Model
{
    use HasFactory;

    protected static function newFactory(): \Kezi\Accounting\Database\Factories\CurrencyRevaluationLineFactory
    {
        return \Kezi\Accounting\Database\Factories\CurrencyRevaluationLineFactory::new();
    }

    protected $table = 'currency_revaluation_lines';

    /** @var list<string> */
    protected $fillable = [
        'currency_revaluation_id',
        'account_id',
        'currency_id',
        'partner_id',
        'foreign_currency_balance',
        'historical_rate',
        'current_rate',
        'book_value',
        'revalued_amount',
        'adjustment_amount',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'foreign_currency_balance' => OriginalCurrencyMoneyCast::class,
        'historical_rate' => 'float',
        'current_rate' => 'float',
        'book_value' => BaseCurrencyMoneyCast::class,
        'revalued_amount' => BaseCurrencyMoneyCast::class,
        'adjustment_amount' => BaseCurrencyMoneyCast::class,
    ];

    /** @var list<string> */
    protected $with = ['currencyRevaluation.company.currency', 'currency'];

    // Relationships

    /** @return BelongsTo<CurrencyRevaluation, static> */
    public function currencyRevaluation(): BelongsTo
    {
        return $this->belongsTo(CurrencyRevaluation::class);
    }

    /** @return BelongsTo<Account, static> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return BelongsTo<Currency, static> */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /** @return BelongsTo<Partner, static> */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    // Business Logic

    /**
     * Check if this line represents a gain.
     */
    public function isGain(): bool
    {
        return $this->adjustment_amount->isPositive();
    }

    /**
     * Check if this line represents a loss.
     */
    public function isLoss(): bool
    {
        return $this->adjustment_amount->isNegative();
    }

    /**
     * Get the rate change percentage.
     */
    public function getRateChangePercentage(): float
    {
        if ($this->historical_rate == 0) {
            return 0.0;
        }

        return (($this->current_rate - $this->historical_rate) / $this->historical_rate) * 100;
    }
}
