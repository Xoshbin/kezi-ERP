<?php

namespace Kezi\Accounting\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Accounting\Database\Factories\LoanAgreementFactory;
use Kezi\Accounting\Enums\Loans\LoanStatus;
use Kezi\Accounting\Enums\Loans\LoanType;
use Kezi\Accounting\Enums\Loans\ScheduleMethod;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $partner_id
 * @property string|null $name
 * @property \Illuminate\Support\Carbon $loan_date
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $maturity_date
 * @property int $duration_months
 * @property int $currency_id
 * @property \Brick\Money\Money $principal_amount
 * @property \Brick\Money\Money $outstanding_principal
 * @property LoanType $loan_type
 * @property LoanStatus $status
 * @property ScheduleMethod $schedule_method
 * @property float $interest_rate
 * @property int $eir_enabled
 * @property float|null $eir_rate
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Currency $currency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Accounting\Models\LoanFeeLine> $feeLines
 * @property-read int|null $fee_lines_count
 * @property-read Partner|null $partner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Accounting\Models\LoanRateChange> $rateChanges
 * @property-read int|null $rate_changes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Accounting\Models\LoanScheduleEntry> $scheduleEntries
 * @property-read int|null $schedule_entries_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement whereDurationMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement whereEirEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement whereEirRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement whereInterestRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement whereLoanDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement whereLoanType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement whereMaturityDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement whereOutstandingPrincipal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement wherePartnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement wherePrincipalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement whereScheduleMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanAgreement whereUpdatedAt($value)
 * @method static \Kezi\Accounting\Database\Factories\LoanAgreementFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class LoanAgreement extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'partner_id',
        'name',
        'loan_date',
        'start_date',
        'maturity_date',
        'duration_months',
        'currency_id',
        'principal_amount',
        'outstanding_principal',
        'loan_type',
        'status',
        'schedule_method',
        'interest_rate',
        'eir_enabled',
        'eir_rate',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'start_date' => 'date',
        'maturity_date' => 'date',
        'principal_amount' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'outstanding_principal' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'loan_type' => LoanType::class,
        'status' => LoanStatus::class,
        'schedule_method' => ScheduleMethod::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function scheduleEntries(): HasMany
    {
        return $this->hasMany(LoanScheduleEntry::class, 'loan_id');
    }

    public function feeLines(): HasMany
    {
        return $this->hasMany(LoanFeeLine::class, 'loan_id');
    }

    public function rateChanges(): HasMany
    {
        return $this->hasMany(LoanRateChange::class, 'loan_id');
    }

    protected static function newFactory()
    {
        return LoanAgreementFactory::new();
    }
}
