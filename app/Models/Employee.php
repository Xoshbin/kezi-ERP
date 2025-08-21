<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use App\Observers\AuditLogObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

/**
 * Class Employee
 *
 * @package App\Models
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
 */
#[ObservedBy([AuditLogObserver::class])]
class Employee extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
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
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user account associated with this employee.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the department this employee belongs to.
     *
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the position this employee holds.
     *
     * @return BelongsTo
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the manager of this employee.
     *
     * @return BelongsTo
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Get the direct reports of this employee.
     *
     * @return HasMany
     */
    public function directReports(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    /**
     * Get the employment contracts for this employee.
     *
     * @return HasMany
     */
    public function employmentContracts(): HasMany
    {
        return $this->hasMany(EmploymentContract::class);
    }

    /**
     * Get the current active employment contract.
     *
     * @return HasOne
     */
    public function currentContract(): HasOne
    {
        return $this->hasOne(EmploymentContract::class)
                    ->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where(function ($query) {
                        $query->whereNull('end_date')
                              ->orWhere('end_date', '>=', now());
                    })
                    ->latest('start_date');
    }

    /**
     * Get the leave requests for this employee.
     *
     * @return HasMany
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get the attendance records for this employee.
     *
     * @return HasMany
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get the payroll records for this employee.
     *
     * @return HasMany
     */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get the employee's full name.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get the employee's display name (full name with employee number).
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name . ' (' . $this->employee_number . ')';
    }

    /**
     * Get the employee's age.
     *
     * @return int|null
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Get the employee's years of service.
     *
     * @return int
     */
    public function getYearsOfServiceAttribute(): int
    {
        return $this->hire_date->diffInYears(now());
    }

    /**
     * Check if the employee is currently active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->employment_status === 'active' &&
               $this->is_active &&
               is_null($this->termination_date);
    }



    /**
     * Check if the employee is on probation.
     *
     * @return bool
     */
    public function isOnProbation(): bool
    {
        $contract = $this->currentContract;

        if (!$contract || !$contract->probation_end_date) {
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
        $subordinates = collect();

        foreach ($this->directReports as $directReport) {
            $subordinates->push($directReport);
            $subordinates = $subordinates->merge($directReport->getAllSubordinates());
        }

        return $subordinates;
    }

    /**
     * Check if this employee is a manager.
     *
     * @return bool
     */
    public function isManager(): bool
    {
        return $this->directReports()->count() > 0;
    }

    /**
     * Get the employee's current leave balance for a specific leave type.
     *
     * @param LeaveType $leaveType
     * @return float
     */
    public function getLeaveBalance(LeaveType $leaveType): float
    {
        $contract = $this->currentContract;

        if (!$contract) {
            return 0;
        }

        // Get entitled days based on leave type and contract
        $entitledDays = match($leaveType->code) {
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
     *
     * @param Carbon $date
     * @return Attendance|null
     */
    public function getAttendanceForDate(Carbon $date): ?Attendance
    {
        return $this->attendances()
                    ->where('attendance_date', $date->format('Y-m-d'))
                    ->first();
    }

    /**
     * Check if the employee has clocked in today.
     *
     * @return bool
     */
    public function hasClockedInToday(): bool
    {
        $todayAttendance = $this->getAttendanceForDate(now());

        return $todayAttendance && $todayAttendance->clock_in_time;
    }

    /**
     * Check if the employee has clocked out today.
     *
     * @return bool
     */
    public function hasClockedOutToday(): bool
    {
        $todayAttendance = $this->getAttendanceForDate(now());

        return $todayAttendance && $todayAttendance->clock_out_time;
    }

    /**
     * Get the employee's latest payroll.
     *
     * @return Payroll|null
     */
    public function getLatestPayroll(): ?Payroll
    {
        return $this->payrolls()
                    ->where('status', '!=', 'cancelled')
                    ->latest('period_end_date')
                    ->first();
    }

    /**
     * Generate a unique employee number.
     *
     * @param Company $company
     * @return string
     */
    public static function generateEmployeeNumber(Company $company): string
    {
        $prefix = 'EMP';
        $year = now()->year;

        // Get the next sequential number for this year
        $lastEmployee = static::where('company_id', $company->id)
            ->where('employee_number', 'like', $prefix . $year . '%')
            ->orderBy('employee_number', 'desc')
            ->first();

        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . $year . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
