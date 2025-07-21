<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalyticAccount extends Model
{
    /**
     * The table associated with the model.
     * Analytic accounts are a cornerstone of management accounting, providing
     * a flexible dimension for internal reporting and cost/revenue allocation.
     *
     * @var string
     */
    protected $table = 'analytic_accounts';

    /**
     * The attributes that are mass assignable.
     * These fields directly map to the 'analytic_accounts' table schema.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',   // Essential for multi-company environments [3]
        'name',         // The human-readable name of the analytic account (e.g., 'Project Alpha', 'Marketing Department') [3]
        'reference',    // An optional internal reference code for the account [3]
        'currency_id',  // Nullable, if the analytic account is specific to a currency [3, 5]
        'is_active',    // Flag to indicate if the account is currently active. Acts as a soft-deprecation for analytic records, preserving historical data [3]
    ];

    /**
     * The attributes that should be cast.
     * 'is_active' is cast to boolean for convenient usage.
     * Timestamps are automatically managed by Eloquent.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active'  => 'boolean',  //
        'created_at' => 'datetime', // Automatically handled by Eloquent [6, 7]
        'updated_at' => 'datetime', // Automatically handled by Eloquent [6, 7]
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    | Analytic accounts are deeply integrated into the financial system,
    | linking to companies, currencies, journal entries, and analytic plans.
    */

    /**
     * Get the company that owns the analytic account.
     * In a multi-company setup, analytic accounts are typically tied to a specific company,
     * though they can be accessible to all if company_id is null, similar to Odoo's approach [5].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the currency associated with the analytic account.
     * This is a nullable relationship, allowing for flexibility in multi-currency tracking [3].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the journal entry lines associated with this analytic account.
     * This demonstrates how analytic accounts provide a distinct layer for tagging
     * financial movements recorded in the general ledger for management analysis [2, 3].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'analytic_account_id');
    }

    /**
     * The analytic plans that belong to this analytic account.
     * Analytic accounts can be grouped by analytic plans, enabling higher-level
     * reporting or budget structures [1, 3, 8-10].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function analyticPlans(): BelongsToMany
    {
        // The pivot table 'analytic_account_plan_pivot' connects analytic accounts to analytic plans [3, 8]
        return $this->belongsToMany(AnalyticPlan::class, 'analytic_account_plan_pivot', 'analytic_account_id', 'analytic_plan_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accounting-Specific Methods (Examples)
    |--------------------------------------------------------------------------
    | These methods highlight how analytic accounts are used in practice,
    | often for financial reporting and budget tracking.
    */

    /**
     * Determine if the analytic account is active.
     * This helps in filtering lists and preventing new allocations to deprecated accounts.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the total budgeted amount for this analytic account across all associated budgets.
     * This would typically involve a deeper relationship to a 'budget_lines' table [11].
     *
     * @return float
     */
    public function getTotalBudgetedAmount(): float
    {
        // Implement logic to sum budgeted_amount from related BudgetLine models
        // Example: return $this->budgetLines()->sum('budgeted_amount');
        // Requires a 'hasMany' relationship to BudgetLine if applicable [11].
        return 0.00; // Placeholder
    }

    /**
     * Get the total actual amount posted to this analytic account from journal entry lines.
     *
     * @return float
     */
    public function getTotalActualAmount(): float
    {
        // Sum of debits minus sum of credits for financial impact [3]
        return $this->journalEntryLines()->sum('debit') - $this->journalEntryLines()->sum('credit');
    }
}
