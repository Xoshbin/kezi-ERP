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
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Accounting\Models\AnalyticAccount;
use Modules\Foundation\Models\Partner;
use Modules\HR\Models\Employee;
use Modules\ProjectManagement\Enums\BillingType;
use Modules\ProjectManagement\Enums\ProjectStatus;

/**
 * Class Project
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $analytic_account_id
 * @property int|null $customer_id
 * @property int|null $manager_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property ProjectStatus $status
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string $budget_amount
 * @property bool $is_billable
 * @property BillingType $billing_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Company $company
 * @property-read AnalyticAccount|null $analyticAccount
 * @property-read Partner|null $customer
 * @property-read Employee|null $manager
 * @property-read Collection<int, ProjectTask> $tasks
 * @property-read int|null $tasks_count
 * @property-read Collection<int, TimesheetLine> $timesheetLines
 * @property-read int|null $timesheet_lines_count
 * @property-read Collection<int, ProjectBudget> $budgets
 * @property-read int|null $budgets_count
 * @property-read Collection<int, ProjectInvoice> $invoices
 * @property-read int|null $invoices_count
 */
#[ObservedBy([\Modules\Foundation\Observers\AuditLogObserver::class])]
class Project extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'analytic_account_id',
        'customer_id',
        'manager_id',
        'name',
        'code',
        'description',
        'status',
        'start_date',
        'end_date',
        'budget_amount',
        'is_billable',
        'billing_type',
        'hourly_rate',
    ];

    protected $casts = [
        'status' => ProjectStatus::class,
        'billing_type' => BillingType::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'is_billable' => 'boolean',
        'hourly_rate' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
        'is_billable' => true,
        'billing_type' => 'time_and_materials',
        'budget_amount' => '0',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<AnalyticAccount, static>
     */
    public function analyticAccount(): BelongsTo
    {
        return $this->belongsTo(AnalyticAccount::class);
    }

    /**
     * @return BelongsTo<Partner, static>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'customer_id');
    }

    /**
     * @return BelongsTo<Employee, static>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * @return HasMany<ProjectTask, static>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    /**
     * @return HasMany<TimesheetLine, static>
     */
    public function timesheetLines(): HasMany
    {
        return $this->hasMany(TimesheetLine::class);
    }

    /**
     * @return HasMany<ProjectBudget, static>
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(ProjectBudget::class);
    }

    /**
     * @return HasMany<ProjectInvoice, static>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(ProjectInvoice::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get the active budget for this project.
     */
    public function getActiveBudget(): ?ProjectBudget
    {
        /** @var ProjectBudget|null $budget */
        $budget = $this->budgets()
            ->where('is_active', true)
            ->latest('created_at')
            ->first();

        return $budget;
    }

    /**
     * Get total actual cost from journal entries via analytic account.
     */
    public function getTotalActualCost(): Money
    {
        if (! $this->analyticAccount) {
            return Money::zero($this->company->currency);
        }

        $debitTotal = $this->analyticAccount->journalEntryLines()
            ->sum('debit');

        $creditTotal = $this->analyticAccount->journalEntryLines()
            ->sum('credit');

        $netAmount = $debitTotal - $creditTotal;

        return Money::ofMinor($netAmount, $this->company->currency->code);
    }

    /**
     * Get total budget amount.
     */
    public function getTotalBudget(): Money
    {
        $activeBudget = $this->getActiveBudget();

        if (! $activeBudget) {
            return Money::ofMinor($this->budget_amount, $this->company->currency->code);
        }

        return Money::ofMinor($activeBudget->total_budget, $this->company->currency->code);
    }

    /**
     * Get budget variance (Budget - Actual).
     */
    public function getBudgetVariance(): Money
    {
        return $this->getTotalBudget()->minus($this->getTotalActualCost());
    }

    /**
     * Get total billable hours from timesheet lines.
     */
    public function getTotalBillableHours(): float
    {
        return $this->timesheetLines()
            ->where('is_billable', true)
            ->whereHas('timesheet', function ($query) {
                $query->where('status', 'approved');
            })
            ->sum('hours');
    }

    /**
     * Get total hours (billable and non-billable).
     */
    public function getTotalHours(): float
    {
        return $this->timesheetLines()
            ->whereHas('timesheet', function ($query) {
                $query->where('status', 'approved');
            })
            ->sum('hours');
    }

    /**
     * Get project completion percentage based on tasks.
     */
    public function getCompletionPercentage(): int
    {
        $totalTasks = $this->tasks()->count();

        if ($totalTasks === 0) {
            return 0;
        }

        $completedTasks = $this->tasks()
            ->where('status', 'completed')
            ->count();

        return (int) round(($completedTasks / $totalTasks) * 100);
    }

    /**
     * Check if project is overbudget.
     */
    public function isOverbudget(): bool
    {
        return $this->getBudgetVariance()->isNegative();
    }

    /**
     * Check if project is active.
     */
    public function isActive(): bool
    {
        return $this->status === ProjectStatus::Active;
    }

    /**
     * Check if project is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === ProjectStatus::Completed;
    }

    /**
     * Check if project is billable.
     */
    public function isBillable(): bool
    {
        return $this->is_billable;
    }

    /**
     * Get project duration in days.
     */
    public function getDurationInDays(): ?int
    {
        if (! $this->start_date || ! $this->end_date) {
            return null;
        }

        return $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Check if project is overdue.
     */
    public function isOverdue(): bool
    {
        if (! $this->end_date) {
            return false;
        }

        return $this->end_date->isPast() && ! $this->isCompleted();
    }

    protected static function newFactory()
    {
        return \Modules\ProjectManagement\Database\Factories\ProjectFactory::new();
    }
}
