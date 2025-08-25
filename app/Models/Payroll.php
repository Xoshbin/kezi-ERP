<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Casts\DocumentCurrencyMoneyCast;
use App\Observers\AuditLogObserver;
use App\Observers\PayrollObserver;
use Carbon\Carbon;

/**
 * Class Payroll
 *
 * @package App\Models
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
 * @property \Brick\Money\Money $base_salary
 * @property \Brick\Money\Money $overtime_amount
 * @property \Brick\Money\Money $housing_allowance
 * @property \Brick\Money\Money $transport_allowance
 * @property \Brick\Money\Money $meal_allowance
 * @property \Brick\Money\Money $other_allowances
 * @property \Brick\Money\Money $bonus
 * @property \Brick\Money\Money $commission
 * @property \Brick\Money\Money $gross_salary
 * @property \Brick\Money\Money $income_tax
 * @property \Brick\Money\Money $social_security
 * @property \Brick\Money\Money $health_insurance
 * @property \Brick\Money\Money $pension_contribution
 * @property \Brick\Money\Money $other_deductions
 * @property \Brick\Money\Money $total_deductions
 * @property \Brick\Money\Money $net_salary
 * @property string $status
 * @property int|null $processed_by_user_id
 * @property Carbon|null $processed_at
 * @property int|null $approved_by_user_id
 * @property Carbon|null $approved_at
 * @property string|null $notes
 * @property array|null $adjustments
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Employee $employee
 * @property-read Currency $currency
 * @property-read JournalEntry|null $journalEntry
 * @property-read Payment|null $payment
 * @property-read User|null $processedByUser
 * @property-read User|null $approvedByUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PayrollLine> $payrollLines
 */
#[ObservedBy([AuditLogObserver::class, PayrollObserver::class])]
class Payroll extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
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
        'base_salary' => DocumentCurrencyMoneyCast::class,
        'overtime_amount' => DocumentCurrencyMoneyCast::class,
        'housing_allowance' => DocumentCurrencyMoneyCast::class,
        'transport_allowance' => DocumentCurrencyMoneyCast::class,
        'meal_allowance' => DocumentCurrencyMoneyCast::class,
        'other_allowances' => DocumentCurrencyMoneyCast::class,
        'bonus' => DocumentCurrencyMoneyCast::class,
        'commission' => DocumentCurrencyMoneyCast::class,
        'gross_salary' => DocumentCurrencyMoneyCast::class,
        'income_tax' => DocumentCurrencyMoneyCast::class,
        'social_security' => DocumentCurrencyMoneyCast::class,
        'health_insurance' => DocumentCurrencyMoneyCast::class,
        'pension_contribution' => DocumentCurrencyMoneyCast::class,
        'other_deductions' => DocumentCurrencyMoneyCast::class,
        'total_deductions' => DocumentCurrencyMoneyCast::class,
        'net_salary' => DocumentCurrencyMoneyCast::class,
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
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee this payroll belongs to.
     *
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the currency for this payroll.
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the journal entry created for this payroll.
     *
     * @return BelongsTo
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Get the payment created for this payroll.
     *
     * @return BelongsTo
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the user who processed this payroll.
     *
     * @return BelongsTo
     */
    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    /**
     * Get the user who approved this payroll.
     *
     * @return BelongsTo
     */
    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * Get the payroll lines for this payroll.
     *
     * @return HasMany
     */
    public function payrollLines(): HasMany
    {
        return $this->hasMany(PayrollLine::class);
    }

    /**
     * Check if the payroll can be paid.
     *
     * @return bool
     */
    public function canBePaid(): bool
    {
        return $this->status === 'processed' && !$this->payment_id;
    }

    /**
     * Check if the payroll is paid.
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid' && $this->payment_id;
    }

    /**
     * Get the full name of the employee for this payroll.
     *
     * @return string
     */
    public function getEmployeeFullNameAttribute(): string
    {
        return $this->employee->first_name . ' ' . $this->employee->last_name;
    }
}
