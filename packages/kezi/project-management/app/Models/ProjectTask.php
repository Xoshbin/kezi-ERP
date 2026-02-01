<?php

namespace Kezi\ProjectManagement\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kezi\HR\Models\Employee;
use Kezi\ProjectManagement\Enums\TaskStatus;

/**
 * Class ProjectTask
 *
 * @property int $id
 * @property int $company_id
 * @property int $project_id
 * @property int|null $parent_task_id
 * @property int|null $assigned_to
 * @property string $name
 * @property string|null $description
 * @property TaskStatus $status
 * @property Carbon|null $start_date
 * @property Carbon|null $due_date
 * @property string $estimated_hours
 * @property string $actual_hours
 * @property int $progress_percentage
 * @property int $sequence
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Company $company
 * @property-read Project $project
 * @property-read ProjectTask|null $parentTask
 * @property-read Collection<int, ProjectTask> $subtasks
 * @property-read int|null $subtasks_count
 * @property-read Employee|null $assignedEmployee
 * @property-read Collection<int, TimesheetLine> $timesheetLines
 * @property-read int|null $timesheet_lines_count
 */
#[ObservedBy([\Kezi\Foundation\Observers\AuditLogObserver::class])]
class ProjectTask extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'project_id',
        'parent_task_id',
        'assigned_to',
        'name',
        'description',
        'status',
        'start_date',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'progress_percentage',
        'sequence',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'start_date' => 'date',
        'due_date' => 'date',
        'progress_percentage' => 'integer',
        'sequence' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
        'estimated_hours' => '0',
        'actual_hours' => '0',
        'progress_percentage' => 0,
        'sequence' => 0,
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Project, static>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<ProjectTask, static>
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'parent_task_id');
    }

    /**
     * @return HasMany<ProjectTask, static>
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'parent_task_id');
    }

    /**
     * @return BelongsTo<Employee, static>
     */
    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    /**
     * @return HasMany<TimesheetLine, static>
     */
    public function timesheetLines(): HasMany
    {
        return $this->hasMany(TimesheetLine::class);
    }

    /**
     * Check if task is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === TaskStatus::Completed;
    }

    /**
     * Check if task is overdue.
     */
    public function isOverdue(): bool
    {
        if (! $this->due_date) {
            return false;
        }

        return $this->due_date->isPast() && ! $this->isCompleted();
    }

    /**
     * Get variance between estimated and actual hours.
     */
    public function getHoursVariance(): float
    {
        return (float) $this->estimated_hours - (float) $this->actual_hours;
    }

    protected static function newFactory()
    {
        return \Kezi\ProjectManagement\Database\Factories\ProjectTaskFactory::new();
    }
}
