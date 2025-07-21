<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class LockDate
 * @package App\Models
 *
 * This Eloquent model defines a 'lock date' for a specific company and type (e.g., tax return, everything).
 * It is a critical component for enforcing immutability of financial records within closed periods,
 * preventing any direct creation or modification of journal entries with an accounting date on or before
 * the specified 'locked_until' date. This adheres strictly to core accounting principles and auditability requirements.
 *
 * @property int $id The unique primary key for this lock date entry.
 * @property int $company_id Foreign key linking this lock date to a specific Company.
 * @property string $lock_type The type of lock being applied (e.g., 'tax_return_date', 'everything_date').
 * @property \Illuminate\Support\Carbon $locked_until The date up to which financial records are locked.
 * @property \Illuminate\Support\Carbon $created_at Timestamp indicating when this lock date was created.
 * @property \Illuminate\Support\Carbon $updated_at Timestamp indicating when this lock date was last updated.
 *
 * @property-read \App\Models\Company $company The Company model associated with this lock date.
 */
class LockDate extends Model
{
    /**
     * The database table associated with the model.
     *
     * Explicitly defines the table name as 'lock_dates' as per schema design [5].
     *
     * @var string
     */
    protected $table = 'lock_dates';

    /**
     * The attributes that are mass assignable.
     *
     * These fields can be safely mass-assigned. The `lock_type` and `locked_until`
     * are essential for defining the lock, and `company_id` establishes the scope [5].
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'lock_type',
        'locked_until',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * The `locked_until` field is cast to a `datetime` object (Carbon instance)
     * for convenient date manipulation and consistency [7].
     *
     * @var array<string, string>
     */
    protected $casts = [
        'locked_until' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    |
    | Eloquent relationships define how this model interacts with other models,
    | providing a fluent and intuitive interface for traversing related data.
    |
    */

    /**
     * Get the Company that this lock date belongs to.
     *
     * A `LockDate` entry is always specific to an individual `Company`,
     * ensuring proper multi-company data segregation [5, 8, 9].
     *
     * @return BelongsTo An Eloquent relationship instance for the `Company` model.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
