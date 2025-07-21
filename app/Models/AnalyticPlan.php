<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class AnalyticPlan
 *
 * @package App\Models
 *
 * This Eloquent model represents an Analytic Plan, a core concept in management
 * accounting systems like Odoo. Analytic plans are used to group and categorize
 * analytic accounts, enabling multi-dimensional analysis of costs and revenues
 * by project, department, or other business segments [1, 3].
 *
 * @property int $id Primary key, auto-incrementing.
 * @property string $name The name of the analytic plan (e.g., 'Project Budget', 'Departmental Costs') [3, 4].
 * @property int|null $parent_id Foreign key to another AnalyticPlan, supporting hierarchical plan structures [3].
 * @property string|null $default_applicability Defines how the plan is applied when creating new journal entries [3].
 * @property string|null $color A color code for visual identification of tags related to this plan [3].
 * @property int|null $company_id Foreign key to the company this analytic plan belongs to. Nullable for shared plans [4-6].
 * @property \Illuminate\Support\Carbon|null $created_at Timestamp when the record was created [4].
 * @property \Illuminate\Support\Carbon|null $updated_at Timestamp when the record was last updated [4].
 *
 * @property-read \App\Models\Company|null $company The company this analytic plan is associated with.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AnalyticAccount[] $analyticAccounts The analytic accounts associated with this plan.
 * @property-read \App\Models\AnalyticPlan|null $parent The parent analytic plan in a hierarchical structure.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AnalyticPlan[] $children The child analytic plans in a hierarchical structure.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Budget[] $budgets The budgets associated with this analytic plan.
 */
class AnalyticPlan extends Model
{
    use HasFactory;

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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(AnalyticPlan::class, 'parent_id');
    }

    /**
     * Get the child analytic plans within this hierarchical structure.
     * Defines the "subplans" mentioned in the sources, allowing for recursive plan definitions [8].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(AnalyticPlan::class, 'parent_id');
    }

    /**
     * Get the budgets associated with this analytic plan.
     * Analytic plans are crucial for defining and tracking budgets related to projects or departments [1, 4, 11].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }
}
