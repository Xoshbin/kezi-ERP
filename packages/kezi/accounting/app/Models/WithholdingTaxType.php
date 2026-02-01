<?php

namespace Kezi\Accounting\Models;

use App\Models\Company;
use Brick\Money\Money;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Kezi\Accounting\Enums\Accounting\WithholdingTaxApplicability;
use Kezi\Foundation\Casts\BaseCurrencyMoneyCast;
use Spatie\Translatable\HasTranslations;

// The SoftDeletes trait is intentionally omitted.
// As per accounting principles, withholding tax type records, once used, should not be physically deleted.
// Instead, they are managed via an 'is_active' flag for historical auditability.
/**
 * @property int $id
 * @property int $company_id
 * @property int $withholding_account_id
 * @property string|array<string, string> $name
 * @property float $rate
 * @property Money|null $threshold_amount
 * @property WithholdingTaxApplicability $applicable_to
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Account $withholdingAccount
 * @property-read float $rate_percentage
 *
 * @method static Builder<static>|WithholdingTaxType active()
 * @method static WithholdingTaxTypeFactory factory($count = null, $state = [])
 * @method static Builder<static>|WithholdingTaxType newModelQuery()
 * @method static Builder<static>|WithholdingTaxType newQuery()
 * @method static Builder<static>|WithholdingTaxType query()
 *
 * @mixin Eloquent
 */
class WithholdingTaxType extends Model
{
    use HasFactory;
    use HasTranslations;

    protected static function newFactory(): \Kezi\Accounting\Database\Factories\WithholdingTaxTypeFactory
    {
        return \Kezi\Accounting\Database\Factories\WithholdingTaxTypeFactory::new();
    }

    /** @var array<int, string> */
    public array $translatable = ['name'];

    /**
     * Get the translatable fields that should be searched.
     *
     * @return array<int, string>
     */
    public function getTranslatableSearchFields(): array
    {
        return ['name'];
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'withholding_tax_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'rate',
        'threshold_amount',
        'applicable_to',
        'withholding_account_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rate' => 'float',
        'threshold_amount' => BaseCurrencyMoneyCast::class,
        'applicable_to' => WithholdingTaxApplicability::class,
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the Company that owns this Withholding Tax Type.
     *
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the Account where withholding tax amounts are posted (e.g., WHT Payable account).
     *
     * @return BelongsTo<Account, static>
     */
    public function withholdingAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'withholding_account_id');
    }

    /**
     * Get the withholding tax entries using this type.
     *
     * @return HasMany<WithholdingTaxEntry, static>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(WithholdingTaxEntry::class);
    }

    /**
     * Scope a query to only include active withholding tax types.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Accessor to get the rate as a percentage for display purposes.
     */
    public function getRatePercentageAttribute(): float
    {
        return (float) $this->rate * 100; // 0.05 -> 5.00%
    }

    /**
     * Get the rate as a fraction (for calculations).
     */
    public function getRateFractionAttribute(): float
    {
        return (float) $this->rate; // 0.05 -> 0.05
    }

    /**
     * Calculate the withholding tax amount for a given base amount.
     */
    public function calculateWithholding(Money $baseAmount): Money
    {
        // Check threshold if applicable
        if ($this->threshold_amount !== null) {
            if ($baseAmount->isLessThan($this->threshold_amount)) {
                return Money::zero($baseAmount->getCurrency()->getCurrencyCode());
            }
        }

        return $baseAmount->multipliedBy($this->rate);
    }

    /**
     * Check if this WHT type applies to services.
     */
    public function appliesToServices(): bool
    {
        return in_array($this->applicable_to, [
            WithholdingTaxApplicability::Services,
            WithholdingTaxApplicability::Both,
        ]);
    }

    /**
     * Check if this WHT type applies to goods.
     */
    public function appliesToGoods(): bool
    {
        return in_array($this->applicable_to, [
            WithholdingTaxApplicability::Goods,
            WithholdingTaxApplicability::Both,
        ]);
    }
}
