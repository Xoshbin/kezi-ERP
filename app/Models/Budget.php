<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\Budgets\BudgetType;
use App\Enums\Budgets\BudgetStatus;

/**
 * Class Budget
 *
 * @package App\Models
 *
 * This Eloquent model represents a financial or analytic budget within the accounting system.
 * It serves as a planning and control tool, allowing organizations to allocate resources
 * and track performance against predefined targets. Budgets can be defined as 'Analytic'
 * for project/department-level tracking or 'Financial' for general ledger account targets [3, 5].
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property \Illuminate\Support\Carbon $period_start_date
 * @property \Illuminate\Support\Carbon $period_end_date
 * @property string $budget_type
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BudgetLine> $budgetLines
 * @property-read int|null $budget_lines_count
 * @property-read \App\Models\Company $company
 * @method static \Database\Factories\BudgetFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereBudgetType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget wherePeriodEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget wherePeriodStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereUpdatedAt($value)
 * @mixin \Eloquent
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
        'currency_id',
    ];

    /**
     * The attributes that should be cast.
     * Ensures date fields are Carbon instances for consistent date manipulation [6].
     *
     * @var array<string, string>
     */
    protected $casts = [
        'budget_type' => BudgetType::class,
        'status' => BudgetStatus::class,
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
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
