<?php

namespace App\Models;

use App\Traits\TranslatableSearch;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Database\Factories\AnalyticPlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Class AnalyticPlan
 *
 * @package App\Models
 *
 * This Eloquent model represents an Analytic Plan, a core concept in management
 * accounting systems like Odoo. Analytic plans are used to group and categorize
 * analytic accounts, enabling multi-dimensional analysis of costs and revenues
 * by project, department, or other business segments [1, 3].
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AnalyticAccountPlanPivot|null $pivot
 * @property-read Collection<int, AnalyticAccount> $analyticAccounts
 * @property-read int|null $analytic_accounts_count
 * @property-read Collection<int, Budget> $budgets
 * @property-read int|null $budgets_count
 * @property-read Collection<int, AnalyticPlan> $children
 * @property-read int|null $children_count
 * @property-read Company $company
 * @property-read AnalyticPlan|null $parent
 * @method static AnalyticPlanFactory factory($count = null, $state = [])
 * @method static Builder<static>|AnalyticPlan newModelQuery()
 * @method static Builder<static>|AnalyticPlan newQuery()
 * @method static Builder<static>|AnalyticPlan query()
 * @method static Builder<static>|AnalyticPlan whereCompanyId($value)
 * @method static Builder<static>|AnalyticPlan whereCreatedAt($value)
 * @method static Builder<static>|AnalyticPlan whereId($value)
 * @method static Builder<static>|AnalyticPlan whereName($value)
 * @method static Builder<static>|AnalyticPlan whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AnalyticPlan extends Model
{
    use HasFactory, HasTranslations;
    use TranslatableSearch;

    public array $translatable = ['name'];

    /**
     * The table associated with the model.
     * This table groups analytic accounts for structured cost and revenue analysis [1].
     *
     * @var string
     */
    protected $table = 'analytic_plans';

    /**
     * The attributes that are mass assignable.
     * These fields are essential for defining the characteristics and relationships of an analytic plan.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'parent_id',
        'default_applicability', // E.g., 'optional', 'mandatory', 'analytic_distribution_model' [3, 6, 7]
        'color',                 // For UI representation, e.g., hex code or predefined string [3]
        'company_id',            // Multi-company support: null means accessible to all companies [5, 6]
    ];

    /**
     * The attributes that should be cast.
     * Ensures timestamps are Carbon instances for consistent date manipulation.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    | Analytic Plans are integral to the management accounting framework,
    | necessitating clear relationships with companies, analytic accounts,
    | and potentially other plans in a hierarchical structure, as well as budgets.
    */
    /**
     * Get the company that owns this analytic plan.
     * An analytic plan can optionally belong to a specific company, or be shared across all [4-6].
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the analytic accounts that are part of this plan.
     * This establishes the many-to-many relationship via the pivot table,
     * allowing for flexible grouping of analytic accounts [1, 4, 8].
     *
     * @return BelongsToMany
     */
    public function analyticAccounts(): BelongsToMany
    {
        // Explicitly using the custom pivot model AnalyticAccountPlanPivot
        // and ensuring timestamps are maintained on the pivot table [9].
        return $this->belongsToMany(
            AnalyticAccount::class,
            'analytic_account_plan_pivot', // Custom pivot table name [4]
            'analytic_plan_id',            // Foreign key on pivot for this model
            'analytic_account_id'          // Foreign key on pivot for the related model
        )->using(AnalyticAccountPlanPivot::class)
            ->withTimestamps(); // Assuming created_at and updated_at exist on the pivot table [4, 10]
    }

    /**
     * Get the parent analytic plan in a hierarchical structure.
     * Allows for building complex, nested analytic plan organizations [3, 8].
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(AnalyticPlan::class, 'parent_id');
    }

    /**
     * Get the child analytic plans within this hierarchical structure.
     * Defines the "subplans" mentioned in the sources, allowing for recursive plan definitions [8].
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(AnalyticPlan::class, 'parent_id');
    }

    /**
     * Get the budgets associated with this analytic plan.
     * Analytic plans are crucial for defining and tracking budgets related to projects or departments [1, 4, 11].
     *
     * @return HasMany
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }
}
