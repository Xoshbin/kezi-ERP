<?php

namespace Modules\Accounting\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Accounting\Database\Factories\LoanAgreementFactory;
use Modules\Accounting\Enums\Loans\LoanStatus;
use Modules\Accounting\Enums\Loans\LoanType;
use Modules\Accounting\Enums\Loans\ScheduleMethod;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;

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
        'principal_amount' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'outstanding_principal' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
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
