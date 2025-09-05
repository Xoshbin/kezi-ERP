<?php

namespace App\Models;

use App\Casts\DocumentCurrencyMoneyCast;
use App\Observers\AuditLogObserver;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class EmploymentContract
 *
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property int $currency_id
 * @property string $contract_number
 * @property string $contract_type
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property bool $is_active
 * @property Money $base_salary
 * @property Money|null $hourly_rate
 * @property string $pay_frequency
 * @property Money $housing_allowance
 * @property Money $transport_allowance
 * @property Money $meal_allowance
 * @property Money $other_allowances
 * @property float $working_hours_per_week
 * @property float $working_days_per_week
 * @property int $annual_leave_days
 * @property int $sick_leave_days
 * @property int $maternity_leave_days
 * @property int $paternity_leave_days
 * @property int|null $probation_period_months
 * @property Carbon|null $probation_end_date
 * @property int $notice_period_days
 * @property string|null $terms_and_conditions
 * @property string|null $job_description
 * @property int|null $approved_by_user_id
 * @property Carbon|null $approved_at
 * @property Carbon|null $signed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Company $company
 * @property-read Employee $employee
 * @property-read Currency $currency
 * @property-read User|null $approvedBy
 */
#[ObservedBy([AuditLogObserver::class])]
class EmploymentContract extends Model
{
    /** @use HasFactory<\Database\Factories\EmploymentContractFactory> */
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
        'contract_number',
        'contract_type',
        'start_date',
        'end_date',
        'is_active',
        'base_salary',
        'hourly_rate',
        'pay_frequency',
        'housing_allowance',
        'transport_allowance',
        'meal_allowance',
        'other_allowances',
        'working_hours_per_week',
        'working_days_per_week',
        'annual_leave_days',
        'sick_leave_days',
        'maternity_leave_days',
        'paternity_leave_days',
        'probation_period_months',
        'probation_end_date',
        'notice_period_days',
        'terms_and_conditions',
        'job_description',
        'approved_by_user_id',
        'approved_at',
        'signed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'base_salary' => DocumentCurrencyMoneyCast::class,
        'hourly_rate' => DocumentCurrencyMoneyCast::class,
        'housing_allowance' => DocumentCurrencyMoneyCast::class,
        'transport_allowance' => DocumentCurrencyMoneyCast::class,
        'meal_allowance' => DocumentCurrencyMoneyCast::class,
        'other_allowances' => DocumentCurrencyMoneyCast::class,
        'working_hours_per_week' => 'decimal:2',
        'working_days_per_week' => 'decimal:1',
        'annual_leave_days' => 'integer',
        'sick_leave_days' => 'integer',
        'maternity_leave_days' => 'integer',
        'paternity_leave_days' => 'integer',
        'probation_period_months' => 'integer',
        'probation_end_date' => 'date',
        'notice_period_days' => 'integer',
        'approved_at' => 'datetime',
        'signed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'contract_type' => 'permanent',
        'is_active' => true,
        'pay_frequency' => 'monthly',
        'working_hours_per_week' => 40.00,
        'working_days_per_week' => 5.0,
        'annual_leave_days' => 21,
        'sick_leave_days' => 10,
        'maternity_leave_days' => 90,
        'paternity_leave_days' => 7,
        'notice_period_days' => 30,
    ];

    /**
     * Get the company that owns the EmploymentContract.
     */
    /**

     * @return BelongsTo<Company, static>

     */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee this contract belongs to.
     */
    /**

     * @return BelongsTo<Employee, static>

     */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the currency for this contract.
     */
    /**

     * @return BelongsTo<Currency, static>

     */

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the user who approved this contract.
     */
    /**

     * @return BelongsTo<User, static>

     */

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get the total monthly compensation (base salary + allowances).
     */
    public function getTotalMonthlyCompensation(): Money
    {
        return $this->base_salary
            ->plus($this->housing_allowance)
            ->plus($this->transport_allowance)
            ->plus($this->meal_allowance)
            ->plus($this->other_allowances);
    }

    /**
     * Get the annual compensation.
     */
    public function getAnnualCompensation(): Money
    {
        $monthlyTotal = $this->getTotalMonthlyCompensation();

        return match ($this->pay_frequency) {
            'monthly' => $monthlyTotal->multipliedBy(12),
            'bi_weekly' => $monthlyTotal->multipliedBy(26),
            'weekly' => $monthlyTotal->multipliedBy(52),
            'hourly' => $this->hourly_rate->multipliedBy($this->working_hours_per_week * 52),
            default => $monthlyTotal->multipliedBy(12),
        };
    }

    /**
     * Check if the contract is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->start_date->isFuture()) {
            return false;
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the employee is currently on probation.
     */
    public function isOnProbation(): bool
    {
        return $this->probation_end_date && $this->probation_end_date->isFuture();
    }

    /**
     * Get the contract duration in months.
     */
    public function getDurationInMonths(): ?int
    {
        if (! $this->end_date) {
            return null; // Permanent contract
        }

        return (int) $this->start_date->diffInMonths($this->end_date);
    }

    /**
     * Check if the contract is approved.
     */
    public function isApproved(): bool
    {
        return ! is_null($this->approved_at) && ! is_null($this->approved_by_user_id);
    }

    /**
     * Check if the contract is signed.
     */
    public function isSigned(): bool
    {
        return ! is_null($this->signed_at);
    }

    /**
     * Generate a unique contract number.
     */
    public static function generateContractNumber(Company $company): string
    {
        $prefix = 'CON';
        $year = now()->year;

        // Get the next sequential number for this year
        $lastContract = static::where('company_id', $company->id)
            ->where('contract_number', 'like', $prefix.$year.'%')
            ->orderBy('contract_number', 'desc')
            ->first();

        if ($lastContract) {
            $lastNumber = (int) substr($lastContract->contract_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix.$year.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
