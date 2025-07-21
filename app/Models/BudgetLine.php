<?php

namespace App\Models;

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
 *
 * @property int $id Primary key, auto-incrementing.
 * @property int $budget_id Foreign key to the parent Budget [3].
 * @property int|null $analytic_account_id Nullable foreign key to an AnalyticAccount, used for 'Analytic' budgets [3].
 * @property int|null $account_id Nullable foreign key to a general ledger Account, used for 'Financial' budgets [3].
 * @property float $budgeted_amount The planned or allocated amount for this budget line [3].
 * @property float $achieved_amount The actual amount incurred/achieved, calculated from confirmed journal entries [3].
 * @property float $committed_amount The amount committed (e.g., from confirmed Purchase Orders), calculated from POs [3].
 * @property \Illuminate\Support\Carbon|null $created_at Timestamp when the record was created.
 * @property \Illuminate\Support\Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read \App\Models\Budget $budget The parent budget this line belongs to.
 * @property-read \App\Models\AnalyticAccount|null $analyticAccount The analytic account associated with this line (if applicable).
 * @property-read \App\Models\Account|null $account The general ledger account associated with this line (if applicable).
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
        'committed_amount',
    ];

    /**
     * The attributes that should be cast.
     * Ensures numerical values are treated as floats and timestamps as Carbon instances [6].
     *
     * @var array<string, string>
     */
    protected $casts = [
        'budgeted_amount' => 'float',
        'achieved_amount' => 'float',
        'committed_amount' => 'float',
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Get the analytic account associated with this budget line (if applicable).
     * This is crucial for granular cost and revenue tracking against projects or departments [3, 5].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account(): BelongsTo
    {
        // Account model is typically in App\Models [4, 8]
        return $this->belongsTo(Account::class);
    }
}
