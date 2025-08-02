<?php

namespace App\Models;

use App\Enums\Accounting\LockDateType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LockDate extends Model
{
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
