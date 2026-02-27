<?php

namespace Kezi\Pos\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Payment\Enums\Payments\PaymentMethod;

/**
 * @property int $id
 * @property int $pos_order_id
 * @property PaymentMethod $payment_method
 * @property int $amount
 * @property int|null $amount_tendered
 * @property int $change_given
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read PosOrder $order
 */
class PosOrderPayment extends Model
{
    /** @use HasFactory<\Kezi\Pos\Database\Factories\PosOrderPaymentFactory> */
    use HasFactory;

    protected static function newFactory(): \Kezi\Pos\Database\Factories\PosOrderPaymentFactory
    {
        return \Kezi\Pos\Database\Factories\PosOrderPaymentFactory::new();
    }

    protected $fillable = [
        'pos_order_id',
        'payment_method',
        'amount',
        'amount_tendered',
        'change_given',
    ];

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'amount' => 'integer',
            'amount_tendered' => 'integer',
            'change_given' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PosOrder::class, 'pos_order_id');
    }
}
