<?php

namespace Modules\Purchase\Models;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Foundation\Casts\DocumentCurrencyMoneyCast;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Foundation\Observers\AuditLogObserver;
use Modules\Purchase\Enums\Purchases\RequestForQuotationStatus;

/**
 * RequestForQuotation Model
 *
 * Represents a request sent to text vendors for pricing.
 *
 * @property int $id
 * @property int $company_id
 * @property int $vendor_id
 * @property int $currency_id
 * @property int|null $created_by_user_id
 * @property string|null $rfq_number
 * @property Carbon $rfq_date
 * @property Carbon|null $valid_until
 * @property string|null $notes
 * @property RequestForQuotationStatus $status
 * @property int|null $converted_to_purchase_order_id
 * @property Carbon|null $converted_at
 * @property Money $subtotal
 * @property Money $tax_total
 * @property Money $total
 * @property float $exchange_rate
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Company $company
 * @property-read Partner $vendor
 * @property-read Currency $currency
 * @property-read User|null $createdBy
 * @property-read PurchaseOrder|null $convertedToPurchaseOrder
 * @property-read \Illuminate\Database\Eloquent\Collection<int, RequestForQuotationLine> $lines
 */
#[ObservedBy([AuditLogObserver::class])]
class RequestForQuotation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'vendor_id',
        'currency_id',
        'created_by_user_id',
        'rfq_number',
        'rfq_date',
        'valid_until',
        'notes',
        'status',
        'converted_to_purchase_order_id',
        'converted_at',
        'subtotal',
        'tax_total',
        'total',
        'exchange_rate',
    ];

    protected $casts = [
        'rfq_date' => 'date',
        'valid_until' => 'date',
        'converted_at' => 'datetime',
        'status' => RequestForQuotationStatus::class,
        'exchange_rate' => 'decimal:8',
        'subtotal' => DocumentCurrencyMoneyCast::class,
        'tax_total' => DocumentCurrencyMoneyCast::class,
        'total' => DocumentCurrencyMoneyCast::class,
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'vendor_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RequestForQuotationLine::class, 'rfq_id');
    }

    public function convertedToPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'converted_to_purchase_order_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeDraft($query)
    {
        return $query->where('status', RequestForQuotationStatus::Draft);
    }

    public function scopeSent($query)
    {
        return $query->where('status', RequestForQuotationStatus::Sent);
    }

    public function scopeBidReceived($query)
    {
        return $query->where('status', RequestForQuotationStatus::BidReceived);
    }

    // =========================================================================
    // Business Logic Helpers
    // =========================================================================

    public function calculateTotals(): void
    {
        $currency = $this->currency ?? $this->currency()->first();
        if (! $currency) {
            return;
        }

        $subtotal = Money::of(0, $currency->code);
        $taxTotal = Money::of(0, $currency->code);

        foreach ($this->lines as $line) {
            $subtotal = $subtotal->plus($line->subtotal);
            $taxTotal = $taxTotal->plus($line->tax_amount);
        }

        $this->subtotal = $subtotal;
        $this->tax_total = $taxTotal;
        $this->total = $subtotal->plus($taxTotal);
    }
}
