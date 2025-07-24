<?php

namespace App\Models;

use App\Observers\AuditLogObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class LockDate
 *
 * @package App\Models
 * 
 * This Eloquent model defines a 'lock date' for a specific company and type (e.g., tax return, everything).
 * It is a critical component for enforcing immutability of financial records within closed periods,
 * preventing any direct creation or modification of journal entries with an accounting date on or before
 * the specified 'locked_until' date. This adheres strictly to core accounting principles and auditability requirements.
 * @property int $id
 * @property int $company_id
 * @property string $lock_type
 * @property \Illuminate\Support\Carbon $locked_until
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @method static \Database\Factories\LockDateFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate whereLockType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate whereLockedUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[ObservedBy([AuditLogObserver::class])] //(to log when periods are closed)
class LockDate extends Model
{
    use HasFactory;
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
