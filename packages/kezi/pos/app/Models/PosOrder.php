<?php

namespace Kezi\Pos\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;

/**
 * @property int $id
 * @property string $uuid
 * @property int $pos_session_id
 * @property int $company_id
 * @property int|null $customer_id
 * @property int $currency_id
 * @property string $order_number
 * @property string $status
 * @property \Kezi\Payment\Enums\Payments\PaymentMethod|null $payment_method
 * @property \Illuminate\Support\Carbon $ordered_at
 * @property \Brick\Money\Money $total_amount
 * @property \Brick\Money\Money $total_tax
 * @property \Brick\Money\Money $discount_amount
 * @property array|null $sector_data
 * @property string|null $notes
 * @property int|null $invoice_id
 * @property-read \Kezi\Pos\Models\PosSession|null $session
 * @property-read \App\Models\Company $company
 * @property-read \Kezi\Foundation\Models\Partner|null $customer
 * @property-read \Kezi\Foundation\Models\Currency $currency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Pos\Models\PosOrderLine> $lines
 * @property-read \Kezi\Sales\Models\Invoice|null $invoice
 */
class PosOrder extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Kezi\Pos\Database\Factories\PosOrderFactory::new();
    }

    protected $fillable = [
        'uuid',
        'pos_session_id',
        'company_id',
        'customer_id',
        'currency_id',
        'order_number',
        'status',
        'payment_method',
        'ordered_at',
        'total_amount',
        'total_tax',
        'discount_amount',
        'sector_data',
        'notes',
        'invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
            'total_amount' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
            'total_tax' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
            'discount_amount' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
            'sector_data' => 'array',
            'payment_method' => \Kezi\Payment\Enums\Payments\PaymentMethod::class,
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'customer_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PosOrderLine::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PosReturn::class, 'original_order_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(\Kezi\Sales\Models\Invoice::class);
    }
}
