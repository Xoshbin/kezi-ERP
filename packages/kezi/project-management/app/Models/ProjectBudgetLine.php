<?php

namespace Kezi\ProjectManagement\Models;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kezi\Accounting\Models\Account;

/**
 * Class ProjectBudgetLine
 *
 * @property int $id
 * @property int $company_id
 * @property int $project_budget_id
 * @property int $account_id
 * @property string|null $description
 * @property string $budgeted_amount
 * @property string $actual_amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read ProjectBudget $projectBudget
 * @property-read Account $account
 */
#[ObservedBy([\Kezi\Foundation\Observers\AuditLogObserver::class, \Kezi\ProjectManagement\Observers\ProjectBudgetLineObserver::class])]
class ProjectBudgetLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'project_budget_id',
        'account_id',
        'description',
        'budgeted_amount',
        'actual_amount',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'budgeted_amount' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'actual_amount' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
    ];

    protected $attributes = [
        'actual_amount' => '0',
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<ProjectBudget, static>
     */
    public function projectBudget(): BelongsTo
    {
        return $this->belongsTo(ProjectBudget::class);
    }

    /**
     * @return BelongsTo<Account, static>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get budgeted amount as Money object.
     */
    public function getBudgetedMoney(): Money
    {
        return $this->budgeted_amount;
    }

    /**
     * Get actual amount as Money object.
     */
    public function getActualMoney(): Money
    {
        return $this->actual_amount;
    }

    /**
     * Get variance (Budget - Actual).
     */
    public function getVariance(): Money
    {
        return $this->getBudgetedMoney()->minus($this->getActualMoney());
    }

    /**
     * Check if this line is over budget.
     */
    public function isOverBudget(): bool
    {
        return $this->getVariance()->isNegative();
    }

    protected static function newFactory()
    {
        return \Kezi\ProjectManagement\Database\Factories\ProjectBudgetLineFactory::new();
    }
}
