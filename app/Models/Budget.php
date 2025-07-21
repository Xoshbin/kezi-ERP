<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Budget
 *
 * @package App\Models
 *
 * This Eloquent model represents a financial or analytic budget within the accounting system.
 * It serves as a planning and control tool, allowing organizations to allocate resources
 * and track performance against predefined targets. Budgets can be defined as 'Analytic'
 * for project/department-level tracking or 'Financial' for general ledger account targets [3, 5].
 *
 * @property int $id Primary key, auto-incrementing.
 * @property int $company_id Foreign key to the Company this budget belongs to [3].
 * @property string $name The name or description of the budget (e.g., 'Marketing Campaign Q1 2024', 'Annual Operating Budget') [3].
 * @property \Illuminate\Support\Carbon $period_start_date The start date of the budget period [3].
 * @property \Illuminate\Support\Carbon $period_end_date The end date of the budget period [3].
 * @property string $budget_type The type of budget, either 'Analytic' or 'Financial' [3].
 * @property string $status The current status of the budget (e.g., 'Draft', 'Open', 'Revised', 'Closed') [3].
 * @property \Illuminate\Support\Carbon|null $created_at Timestamp when the record was created.
 * @property \Illuminate\Support\Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read \App\Models\Company $company The company to which this budget belongs.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\BudgetLine[] $budgetLines The detailed lines comprising this budget.
 */
class Budget extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * Budgets are fundamental for planning and financial control [3].
     *
     * @var string
     */
    protected $table = 'budgets';

    /**
     * The attributes that are mass assignable.
     * These fields are crucial for creating and managing a budget's core properties.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'period_start_date',
        'period_end_date',
        'budget_type',
        'status',
    ];

    /**
     * The attributes that should be cast.
     * Ensures date fields are Carbon instances for consistent date manipulation [6].
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    | A budget is directly linked to a company and comprises multiple budget lines.
    */

    /**
     * Get the company that owns this budget.
     * Essential for multi-company accounting setups [3].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the budget lines for this budget.
     * Each budget is composed of one or more detailed lines [3].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function budgetLines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }
}
