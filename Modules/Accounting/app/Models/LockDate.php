<?php

namespace Modules\Accounting\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Accounting\Database\Factories\LockDateFactory;
use Modules\Accounting\Enums\Accounting\LockDateType;
use Modules\Accounting\Observers\LockDateObserver;

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
        return \Modules\Accounting\Database\Factories\LockDateFactory::new();
    }
}
