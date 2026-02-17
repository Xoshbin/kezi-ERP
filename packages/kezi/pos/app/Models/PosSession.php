<?php

namespace Kezi\Pos\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $pos_profile_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $opened_at
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property string $status
 * @property \Brick\Money\Money|null $opening_cash
 * @property \Brick\Money\Money|null $closing_cash
 * @property-read \Kezi\Pos\Models\PosProfile $profile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Pos\Models\PosOrder> $orders
 */
class PosSession extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Kezi\Pos\Database\Factories\PosSessionFactory::new();
    }

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
            'opening_cash' => \Kezi\Pos\Casts\PosSessionMoneyCast::class,
            'closing_cash' => \Kezi\Pos\Casts\PosSessionMoneyCast::class,
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
