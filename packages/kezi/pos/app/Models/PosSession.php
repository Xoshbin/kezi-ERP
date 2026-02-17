<?php

namespace Kezi\Pos\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Foundation\Casts\MoneyCast;

class PosSession extends Model
{
    protected $fillable = [
        'pos_profile_id',
        'user_id',
        'opened_at',
        'closed_at',
        'opening_cash',
        'closing_cash',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => MoneyCast::class,
            'closing_cash' => MoneyCast::class,
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PosProfile::class, 'pos_profile_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PosOrder::class);
    }
}
