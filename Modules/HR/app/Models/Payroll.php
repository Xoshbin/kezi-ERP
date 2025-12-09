<?php

namespace Modules\HR\Models;

use Carbon\Carbon;
use App\Models\User;
use Brick\Money\Money;
use App\Models\Company;
use Modules\HR\Models\Employee;
use Modules\HR\Models\PayrollLine;
use Modules\Payment\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Modules\Foundation\Models\Currency;
use Modules\HR\Observers\PayrollObserver;
use Modules\Accounting\Models\JournalEntry;
use Illuminate\Database\Eloquent\Collection;
use Modules\Foundation\Observers\AuditLogObserver;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Modules\Foundation\Casts\DocumentCurrencyMoneyCast;

/**
 * Class Payroll
 *
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property int $currency_id
 * @property int|null $journal_entry_id
 * @property int|null $payment_id
 * @property string $payroll_number
 * @property Carbon $period_start_date
 * @property Carbon $period_end_date
 * @property Carbon $pay_date
 * @property string $pay_frequency
 * @property Money $base_salary
 * @property Money $overtime_amount
 * @property Money $housing_allowance
 * @property Money $transport_allowance
 * @property Money $meal_allowance
 * @property Money $other_allowances
 * @property Money $bonus
 * @property Money $commission
 * @property Money $gross_salary
 * @property Money $income_tax
 * @property Money $social_security
 * @property Money $health_insurance
 * @property Money $pension_contribution
 * @property Money $other_deductions
 * @property Money $total_deductions
 * @property Money $net_salary
 * @property string $status
 * @property int|null $processed_by_user_id
 * @property Carbon|null $processed_at
 * @property int|null $approved_by_user_id
 * @property Carbon|null $approved_at
 * @property string|null $notes
 * @property array<string, mixed>|null $adjustments
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Employee $employee
 * @property-read Currency $currency
 * @property-read JournalEntry|null $journalEntry
 * @property-read Payment|null $payment
 * @property-read User|null $processedByUser
 * @property-read User|null $approvedByUser
 * @property-read Collection<int, PayrollLine> $payrollLines
 */
#[ObservedBy([\Modules\Foundation\Observers\AuditLogObserver::class, PayrollObserver::class])]
class Payroll extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'employee_id',
        'currency_id',
        'journal_entry_id',
        'payment_id',
        'payroll_number',
        'period_start_date',
        'period_end_date',
        'pay_date',
        'pay_frequency',
        'base_salary',
        'overtime_amount',
        'housing_allowance',
        'transport_allowance',
        'meal_allowance',
        'other_allowances',
        'bonus',
        'commission',
        'gross_salary',
        'income_tax',
        'social_security',
        'health_insurance',
        'pension_contribution',
        'other_deductions',
        'total_deductions',
        'net_salary',
        'status',
        'processed_by_user_id',
        'processed_at',
        'approved_by_user_id',
        'approved_at',
        'notes',
        'adjustments',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'pay_date' => 'date',
        'base_salary' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'overtime_amount' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'housing_allowance' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'transport_allowance' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'meal_allowance' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'other_allowances' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'bonus' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'commission' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'gross_salary' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'income_tax' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'social_security' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'health_insurance' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'pension_contribution' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'other_deductions' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'total_deductions' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'net_salary' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'processed_at' => 'datetime',
        'approved_at' => 'datetime',
        'adjustments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * Get the company that owns the Payroll.
     */
    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee this payroll belongs to.
     */
    /**
     * @return BelongsTo<Employee, static>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the currency for this payroll.
     */
    /**
     * @return BelongsTo<Currency, static>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the journal entry created for this payroll.
     */
    /**
     * @return BelongsTo<JournalEntry, static>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Get the payment created for this payroll.
     */
    /**
     * @return BelongsTo<Payment, static>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the user who processed this payroll.
     */
    /**
     * @return BelongsTo<User, static>
     */
    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    /**
     * Get the user who approved this payroll.
     */
    /**
     * @return BelongsTo<User, static>
     */
    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * Get the payroll lines for this payroll.
     */
    /**
     * @return HasMany<PayrollLine, static>
     */
    public function payrollLines(): HasMany
    {
        return $this->hasMany(PayrollLine::class);
    }

    /**
     * Check if the payroll can be paid.
     */
    public function canBePaid(): bool
    {
        return $this->status === 'processed' && ! $this->payment_id;
    }

    /**
     * Check if the payroll is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid' && $this->payment_id;
    }

    /**
     * Get the full name of the employee for this payroll.
     */
    public function getEmployeeFullNameAttribute(): string
    {
        return $this->employee->first_name . ' ' . $this->employee->last_name;
    }
}
