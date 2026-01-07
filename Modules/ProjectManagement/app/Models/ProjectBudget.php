<?php

namespace Modules\ProjectManagement\Models;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class ProjectBudget
 *
 * @property int $id
 * @property int $company_id
 * @property int $project_id
 * @property string $name
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string $total_budget
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Project $project
 * @property-read Collection<int, ProjectBudgetLine> $lines
 * @property-read int|null $lines_count
 */
#[ObservedBy([\Modules\Foundation\Observers\AuditLogObserver::class])]
class ProjectBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'project_id',
        'name',
        'start_date',
        'end_date',
        'total_budget',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'is_active' => true,
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
     * @return HasMany<ProjectBudgetLine, static>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(ProjectBudgetLine::class);
    }

    /**
     * Get total budgeted amount as Money object.
     */
    public function getTotalBudgetMoney(): Money
    {
        return Money::ofMinor($this->total_budget, $this->company->currency->code);
    }

    /**
     * Get total actual amount from budget lines.
     */
    public function getTotalActual(): Money
    {
        $total = $this->lines()->sum('actual_amount');

        return Money::ofMinor($total, $this->company->currency->code);
    }

    /**
     * Get budget utilization percentage.
     */
    public function getUtilizationPercentage(): float
    {
        $budget = (float) $this->total_budget;

        if ($budget == 0) {
            return 0;
        }

        $actual = (float) $this->lines()->sum('actual_amount');

        return round(($actual / $budget) * 100, 2);
    }

    /**
     * Check if budget is overrun.
     */
    public function isOverrun(): bool
    {
        return $this->getTotalActual()->isGreaterThan($this->getTotalBudgetMoney());
    }

    protected static function newFactory()
    {
        return \Modules\ProjectManagement\Database\Factories\ProjectBudgetFactory::new();
    }
}
