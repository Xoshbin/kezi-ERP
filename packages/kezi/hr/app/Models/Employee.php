<?php

namespace Kezi\HR\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kezi\HR\Observers\EmployeeObserver;

/**
 * Class Employee
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $user_id
 * @property int|null $department_id
 * @property int|null $position_id
 * @property int|null $manager_id
 * @property string $employee_number
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone
 * @property Carbon|null $date_of_birth
 * @property string|null $gender
 * @property string|null $marital_status
 * @property string|null $nationality
 * @property string|null $national_id
 * @property string|null $passport_number
 * @property string|null $address_line_1
 * @property string|null $address_line_2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip_code
 * @property string|null $country
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $emergency_contact_relationship
 * @property Carbon $hire_date
 * @property Carbon|null $termination_date
 * @property string $employment_status
 * @property string $employee_type
 * @property string|null $bank_name
 * @property string|null $bank_account_number
 * @property string|null $bank_routing_number
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Company $company
 * @property-read User|null $user
 * @property-read Department|null $department
 * @property-read Position|null $position
 * @property-read Employee|null $manager
 * @property-read Collection<int, Employee> $directReports
 * @property-read int|null $direct_reports_count
 * @property-read Collection<int, EmploymentContract> $employmentContracts
 * @property-read int|null $employment_contracts_count
 * @property-read EmploymentContract|null $currentContract
 * @property-read Collection<int, LeaveRequest> $leaveRequests
 * @property-read int|null $leave_requests_count
 * @property-read Collection<int, Attendance> $attendances
 * @property-read int|null $attendances_count
 * @property-read Collection<int, Payroll> $payrolls
 * @property-read int|null $payrolls_count
 * @property-read Collection<int, CashAdvance> $cashAdvances
 * @property-read int|null $cash_advances_count
 * @property-read int|null $age
 * @property-read string $display_name
 * @property-read string $full_name
 * @property-read int $years_of_service
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAddressLine1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAddressLine2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBankAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBankRoutingNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDateOfBirth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmergencyContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmergencyContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmergencyContactRelationship($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmployeeNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmployeeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmploymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereHireDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereMaritalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereNationalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereNationality($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePassportNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereTerminationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereZipCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withoutTrashed()
 * @method static \Kezi\HR\Database\Factories\EmployeeFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[ObservedBy([\Kezi\Foundation\Observers\AuditLogObserver::class, EmployeeObserver::class])]
class Employee extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'user_id',
        'department_id',
        'position_id',
        'manager_id',
        'employee_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'marital_status',
        'nationality',
        'national_id',
        'passport_number',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'hire_date',
        'termination_date',
        'employment_status',
        'employee_type',
        'bank_name',
        'bank_account_number',
        'bank_routing_number',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'is_active' => 'boolean',
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
        'employment_status' => 'active',
        'employee_type' => 'full_time',
        'is_active' => true,
    ];

    /**
     * Get the company that owns the Employee.
     */
    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user account associated with this employee.
     */
    /**
     * @return BelongsTo<User, static>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the department this employee belongs to.
     */
    /**
     * @return BelongsTo<Department, static>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the position this employee holds.
     */
    /**
     * @return BelongsTo<Position, static>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the manager of this employee.
     */
    /**
     * @return BelongsTo<Employee, static>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Get the direct reports of this employee.
     */
    /**
     * @return HasMany<Employee, static>
     */
    public function directReports(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    /**
     * Get the employment contracts for this employee.
     */
    /**
     * @return HasMany<EmploymentContract, static>
     */
    public function employmentContracts(): HasMany
    {
        return $this->hasMany(EmploymentContract::class);
    }

    /**
     * Get the current active employment contract.
     */
    /**
     * @return HasOne<EmploymentContract, static>
     */
    public function currentContract(): HasOne
    {
        return $this->hasOne(EmploymentContract::class)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->latest('start_date');
    }

    /**
     * Get the leave requests for this employee.
     */
    /**
     * @return HasMany<LeaveRequest, static>
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get the attendance records for this employee.
     */
    /**
     * @return HasMany<Attendance, static>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get the payroll records for this employee.
     */
    /**
     * @return HasMany<Payroll, static>
     */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    /**
     * Get the cash advances for this employee.
     */
    /**
     * @return HasMany<CashAdvance, static>
     */
    public function cashAdvances(): HasMany
    {
        return $this->hasMany(CashAdvance::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get the employee's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Get the employee's display name (full name with employee number).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name.' ('.$this->employee_number.')';
    }

    /**
     * Get the employee's age.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Get the employee's years of service.
     */
    public function getYearsOfServiceAttribute(): int
    {
        return (int) $this->hire_date->diffInYears(now());
    }

    /**
     * Check if the employee is currently active.
     */
    public function isActive(): bool
    {
        return $this->employment_status === 'active' &&
            $this->is_active &&
            is_null($this->termination_date);
    }

    /**
     * Check if the employee is on probation.
     */
    public function isOnProbation(): bool
    {
        $contract = $this->currentContract;

        if (! $contract || ! $contract->probation_end_date) {
            return false;
        }

        return $contract->probation_end_date->isFuture();
    }

    /**
     * Get all subordinates (direct and indirect reports).
     *
     * @return Collection<int, Employee>
     */
    public function getAllSubordinates(): Collection
    {
        $subordinates = new Collection;

        foreach ($this->directReports as $directReport) {
            $subordinates->push($directReport);
            $subordinates = $subordinates->merge($directReport->getAllSubordinates());
        }

        return $subordinates;
    }

    /**
     * Check if this employee is a manager.
     */
    public function isManager(): bool
    {
        return $this->directReports()->count() > 0;
    }

    /**
     * Get the employee's current leave balance for a specific leave type.
     */
    public function getLeaveBalance(LeaveType $leaveType): float
    {
        $contract = $this->currentContract;

        if (! $contract) {
            return 0;
        }

        // Get entitled days based on leave type and contract
        $entitledDays = match ($leaveType->code) {
            'annual' => $contract->annual_leave_days,
            'sick' => $contract->sick_leave_days,
            'maternity' => $contract->maternity_leave_days,
            'paternity' => $contract->paternity_leave_days,
            default => $leaveType->default_days_per_year,
        };

        // Calculate used days for current year
        $usedDays = $this->leaveRequests()
            ->where('leave_type_id', $leaveType->id)
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year)
            ->sum('days_requested');

        return max(0, $entitledDays - $usedDays);
    }

    /**
     * Get the employee's attendance for a specific date.
     */
    public function getAttendanceForDate(Carbon $date): ?Attendance
    {
        /** @var Attendance|null $attendance */
        $attendance = $this->attendances()
            ->whereDate('attendance_date', $date)
            ->first();

        return $attendance;
    }

    /**
     * Check if the employee has clocked in today.
     */
    public function hasClockedInToday(): bool
    {
        $todayAttendance = $this->getAttendanceForDate(now());

        return $todayAttendance && $todayAttendance->clock_in_time;
    }

    /**
     * Check if the employee has clocked out today.
     */
    public function hasClockedOutToday(): bool
    {
        $todayAttendance = $this->getAttendanceForDate(now());

        return $todayAttendance && $todayAttendance->clock_out_time;
    }

    /**
     * Get the employee's latest payroll.
     */
    public function getLatestPayroll(): ?Payroll
    {
        /** @var Payroll|null $payroll */
        $payroll = $this->payrolls()
            ->where('status', '!=', 'cancelled')
            ->latest('period_end_date')
            ->first();

        return $payroll;
    }

    /**
     * Generate a unique employee number.
     */
    public static function generateEmployeeNumber(Company $company): string
    {
        $prefix = 'EMP';
        $year = now()->year;

        // Get the next sequential number for this year
        $lastEmployee = static::where('company_id', $company->id)
            ->where('employee_number', 'like', $prefix.$year.'%')
            ->orderBy('employee_number', 'desc')
            ->first();

        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix.$year.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    protected static function newFactory()
    {
        return \Kezi\HR\Database\Factories\EmployeeFactory::new();
    }
}
