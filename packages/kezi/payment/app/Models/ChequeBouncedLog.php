<?php

namespace Kezi\Payment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $cheque_id
 * @property \Illuminate\Support\Carbon $bounced_at
 * @property string $reason
 * @property \Brick\Money\Money|null $bank_charges
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Kezi\Payment\Models\Cheque $cheque
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChequeBouncedLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChequeBouncedLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChequeBouncedLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChequeBouncedLog whereBankCharges($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChequeBouncedLog whereBouncedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChequeBouncedLog whereChequeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChequeBouncedLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChequeBouncedLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChequeBouncedLog whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChequeBouncedLog whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChequeBouncedLog whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ChequeBouncedLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'cheque_id',
        'bounced_at',
        'reason',
        'bank_charges',
        'notes',
    ];

    protected $casts = [
        'bounced_at' => 'datetime',
        'bank_charges' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
    ];

    public function cheque(): BelongsTo
    {
        return $this->belongsTo(Cheque::class);
    }
}
