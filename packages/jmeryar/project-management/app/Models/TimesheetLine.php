<?php

namespace Jmeryar\ProjectManagement\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class TimesheetLine
 *
 * @property int $id
 * @property int $company_id
 * @property int $timesheet_id
 * @property int|null $project_id
 * @property int|null $project_task_id
 * @property Carbon $date
 * @property string $hours
 * @property string|null $description
 * @property bool $is_billable
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Timesheet $timesheet
 * @property-read Project|null $project
 * @property-read ProjectTask|null $projectTask
 */
#[ObservedBy([
    \Jmeryar\Foundation\Observers\AuditLogObserver::class,
    \Jmeryar\ProjectManagement\Observers\TimesheetLineObserver::class,
])]
class TimesheetLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'timesheet_id',
        'project_id',
        'project_task_id',
        'date',
        'hours',
        'description',
        'is_billable',
    ];

    protected $casts = [
        'date' => 'date',
        'is_billable' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'is_billable' => true,
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Timesheet, static>
     */
    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
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
    public function projectTask(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class);
    }

    protected static function newFactory()
    {
        return \Jmeryar\ProjectManagement\Database\Factories\TimesheetLineFactory::new();
    }
}
