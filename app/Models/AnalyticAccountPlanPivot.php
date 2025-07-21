<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class AnalyticAccountPlanPivot
 *
 * @package App\Models
 *
 * This Eloquent model represents the pivot table for the many-to-many relationship
 * between AnalyticAccount and AnalyticPlan. It is essential for structuring
 * and categorizing analytic accounts within various management accounting plans,
 * enabling flexible cost and revenue analysis across different dimensions.
 *
 * @property int $analytic_account_id The foreign key to the analytic_accounts table.
 * @property int $analytic_plan_id    The foreign key to the analytic_plans table.
 * @property \Illuminate\Support\Carbon|null $created_at Timestamp when the pivot record was created.
 * @property \Illuminate\Support\Carbon|null $updated_at Timestamp when the pivot record was last updated.
 */
class AnalyticAccountPlanPivot extends Pivot
{
    /**
     * The table associated with the model.
     * This table connects Analytic Accounts to Analytic Plans, allowing for
     * flexible grouping and multi-dimensional analysis in management accounting.
     *
     * @var string
     */
    protected $table = 'analytic_account_plan_pivot';

    /**
     * The attributes that are mass assignable.
     * These are the foreign keys that establish the many-to-many relationship.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'analytic_account_id', // Links to the specific analytic account 
        'analytic_plan_id',    // Links to the analytic plan it belongs to 
    ];

    /**
     * The attributes that should be cast.
     * Eloquent automatically manages 'created_at' and 'updated_at' for Pivot models
     * when `withTimestamps()` is called on the relationship definition.
     * Explicitly casting them here ensures consistent Carbon instances.
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
    | Pivot models typically define inverse relationships back to their parent models.
    */

    /**
     * Get the analytic account that this pivot record is associated with.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function analyticAccount(): BelongsTo
    {
        return $this->belongsTo(AnalyticAccount::class);
    }

    /**
     * Get the analytic plan that this pivot record is associated with.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function analyticPlan(): BelongsTo
    {
        return $this->belongsTo(AnalyticPlan::class);
    }
}
