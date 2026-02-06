<?php

namespace Kezi\ProjectManagement\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Kezi\HR\Models\Employee;
use Kezi\ProjectManagement\Enums\TimesheetStatus;

/**
 * Class Timesheet
 *
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property TimesheetStatus $status
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property string|null $rejection_reason
 * @property string $total_hours
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Employee $employee
 * @property-read User|null $approver
 * @property-read Collection<int, TimesheetLine> $lines
 * @property-read int|null $lines_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereTotalHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timesheet whereUpdatedAt($value)
 * @method static \Kezi\ProjectManagement\Database\Factories\TimesheetFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[ObservedBy([
    \Kezi\Foundation\Observers\AuditLogObserver::class,
    \Kezi\ProjectManagement\Observers\TimesheetObserver::class,
])]
class Timesheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'start_date',
        'end_date',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'total_hours',
    ];

    protected $casts = [
        'status' => TimesheetStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
        'total_hours' => '0',
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Employee, static>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, static>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<TimesheetLine, static>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(TimesheetLine::class);
    }

    /**
     * Check if timesheet is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === TimesheetStatus::Draft;
    }

    /**
     * Check if timesheet is submitted.
     */
    public function isSubmitted(): bool
    {
        return $this->status === TimesheetStatus::Submitted;
    }

    /**
     * Check if timesheet is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === TimesheetStatus::Approved;
    }

    /**
     * Check if timesheet is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === TimesheetStatus::Rejected;
    }

    /**
     * Recalculate total hours from lines.
     */
    public function recalculateTotalHours(): void
    {
        $this->total_hours = $this->lines()->sum('hours');
        $this->saveQuietly();
    }

    protected static function newFactory()
    {
        return \Kezi\ProjectManagement\Database\Factories\TimesheetFactory::new();
    }
}
