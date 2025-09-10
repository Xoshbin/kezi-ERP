<?php

namespace App\Models;

use App\Enums\Budgets\BudgetStatus;
use App\Enums\Budgets\BudgetType;
use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class Budget
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property Carbon $period_start_date
 * @property Carbon $period_end_date
 * @property string $budget_type
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, BudgetLine> $budgetLines
 * @property-read int|null $budget_lines_count
 * @property-read Company $company
 *
 * @method static BudgetFactory factory($count = null, $state = [])
 * @method static Builder<static>|Budget newModelQuery()
 * @method static Builder<static>|Budget newQuery()
 * @method static Builder<static>|Budget query()
 * @method static Builder<static>|Budget whereBudgetType($value)
 * @method static Builder<static>|Budget whereCompanyId($value)
 * @method static Builder<static>|Budget whereCreatedAt($value)
 * @method static Builder<static>|Budget whereId($value)
 * @method static Builder<static>|Budget whereName($value)
 * @method static Builder<static>|Budget wherePeriodEndDate($value)
 * @method static Builder<static>|Budget wherePeriodStartDate($value)
 * @method static Builder<static>|Budget whereStatus($value)
 * @method static Builder<static>|Budget whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Budget extends Model
{
    /** @use HasFactory<\Database\Factories\BudgetFactory> */
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
     * @var list<string>
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
     */
    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the budget lines for this budget.
     * Each budget is composed of one or more detailed lines [3].
     */
    /**
     * @return HasMany<BudgetLine, static>
     */
    public function budgetLines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }

    /**
     * @return BelongsTo<Currency, static>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
