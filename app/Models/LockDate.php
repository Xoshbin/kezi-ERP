<?php

namespace App\Models;

use App\Enums\Accounting\LockDateType;
use App\Observers\LockDateObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([LockDateObserver::class])]
class LockDate extends Model
{
    /** @use HasFactory<\Database\Factories\LockDateFactory> */
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
}
