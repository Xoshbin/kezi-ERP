<?php

namespace Kezi\Accounting\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Accounting\Database\Factories\LockDateFactory;
use Kezi\Accounting\Enums\Accounting\LockDateType;
use Kezi\Accounting\Observers\LockDateObserver;

/**
 * @property int $id
 * @property int $company_id
 * @property LockDateType $lock_type
 * @property \Carbon\Carbon $locked_until
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Company $company
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate whereLockType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate whereLockedUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LockDate whereUpdatedAt($value)
 * @method static \Kezi\Accounting\Database\Factories\LockDateFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[ObservedBy([LockDateObserver::class])]
class LockDate extends Model
{
    /** @use HasFactory<\Kezi\Accounting\Database\Factories\LockDateFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'lock_type',
        'locked_until',
    ];

    protected $casts = [
        'lock_type' => LockDateType::class,
        'locked_until' => 'date',
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected static function newFactory(): LockDateFactory
    {
        return \Kezi\Accounting\Database\Factories\LockDateFactory::new();
    }
}
