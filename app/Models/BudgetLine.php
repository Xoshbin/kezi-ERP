<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Database\Factories\BudgetLineFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class BudgetLine
 *
 * @package App\Models
 *
 * This Eloquent model represents a single line item within a Budget.
 * It tracks specific budgeted amounts against actual achieved and committed amounts,
 * linking to either general ledger accounts (for financial budgets) or analytic accounts
 * (for analytic budgets) [3].
 * @property int $id
 * @property int $budget_id
 * @property int|null $analytic_account_id
 * @property int|null $account_id
 * @property float $budgeted_amount
 * @property float $achieved_amount
 * @property float $committed_amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Account|null $account
 * @property-read AnalyticAccount|null $analyticAccount
 * @property-read Budget $budget
 * @method static BudgetLineFactory factory($count = null, $state = [])
 * @method static Builder<static>|BudgetLine newModelQuery()
 * @method static Builder<static>|BudgetLine newQuery()
 * @method static Builder<static>|BudgetLine query()
 * @method static Builder<static>|BudgetLine whereAccountId($value)
 * @method static Builder<static>|BudgetLine whereAchievedAmount($value)
 * @method static Builder<static>|BudgetLine whereAnalyticAccountId($value)
 * @method static Builder<static>|BudgetLine whereBudgetId($value)
 * @method static Builder<static>|BudgetLine whereBudgetedAmount($value)
 * @method static Builder<static>|BudgetLine whereCommittedAmount($value)
 * @method static Builder<static>|BudgetLine whereCreatedAt($value)
 * @method static Builder<static>|BudgetLine whereId($value)
 * @method static Builder<static>|BudgetLine whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BudgetLine extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * Each budget line details a specific financial allocation [3].
     *
     * @var string
     */
    protected $table = 'budget_lines';

    /**
     * The attributes that are mass assignable.
     * These fields define the specifics of a budgeted item.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'budget_id',
        'analytic_account_id',
        'account_id',
        'budgeted_amount',
        'achieved_amount',
        'committed_amount'
    ];

    /**
     * The attributes that should be cast.
     * Ensures numerical values are treated as floats and timestamps as Carbon instances [6].
     *
     * @var array<string, string>
     */
    protected $casts = [
        'budgeted_amount' => MoneyCast::class,
        'achieved_amount' => MoneyCast::class,
        'committed_amount' => MoneyCast::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    | Budget lines connect to their parent budget and optionally to specific
    | general ledger or analytic accounts [3].
    */
    /**
     * Get the budget that owns this budget line.
     *
     * @return BelongsTo
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Get the analytic account associated with this budget line (if applicable).
     * This is crucial for granular cost and revenue tracking against projects or departments [3, 5].
     *
     * @return BelongsTo
     */
    public function analyticAccount(): BelongsTo
    {
        // AnalyticAccount model is typically in App\Models [7]
        return $this->belongsTo(AnalyticAccount::class);
    }

    /**
     * Get the general ledger account associated with this budget line (if applicable).
     * This links the budget line to the Chart of Accounts [3, 4].
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        // Account model is typically in App\Models [4, 8]
        return $this->belongsTo(Account::class);
    }

    /**
     * Accessor to provide the currency_id to the MoneyCast.
     * This robust implementation prevents N+1 query issues.
     */
    public function getCurrencyIdAttribute(): int
    {
        // If the relationship is already loaded, use it. Otherwise, use the foreign key.
        return $this->budget->currency_id ?? $this->budget()->getForeignKeyResults()->first()->currency_id;
    }
}
