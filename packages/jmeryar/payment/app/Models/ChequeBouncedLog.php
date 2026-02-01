<?php

namespace Jmeryar\Payment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'bank_charges' => \Jmeryar\Foundation\Casts\BaseCurrencyMoneyCast::class,
    ];

    public function cheque(): BelongsTo
    {
        return $this->belongsTo(Cheque::class);
    }
}
