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
 */
#[ObservedBy([LockDateObserver::class])]
class LockDate extends Model
{
    /** @use HasFactory<LockDateFactory> */
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
