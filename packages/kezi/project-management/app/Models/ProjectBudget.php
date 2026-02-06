<?php

namespace Kezi\ProjectManagement\Models;

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
 * @property \Brick\Money\Money $total_actual
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBudget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBudget newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBudget query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBudget whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBudget whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBudget whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBudget whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBudget whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBudget whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBudget whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBudget whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBudget whereTotalActual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBudget whereTotalBudget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBudget whereUpdatedAt($value)
 * @method static \Kezi\ProjectManagement\Database\Factories\ProjectBudgetFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[ObservedBy([\Kezi\Foundation\Observers\AuditLogObserver::class])]
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
        'total_actual',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'total_budget' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'total_actual' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
    ];

    protected $attributes = [
        'is_active' => true,
        'total_budget' => 0,
        'total_actual' => 0,
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
        return $this->total_budget;
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
        $budget = $this->total_budget->getAmount()->toFloat();

        if ($budget == 0) {
            return 0;
        }

        $actual = $this->getTotalActual()->getAmount()->toFloat();

        return (float) number_format(($actual / $budget) * 100, 2);
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
        return \Kezi\ProjectManagement\Database\Factories\ProjectBudgetFactory::new();
    }
}
