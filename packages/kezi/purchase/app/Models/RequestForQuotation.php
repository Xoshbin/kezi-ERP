<?php

namespace Kezi\Purchase\Models;

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
use Kezi\Foundation\Casts\DocumentCurrencyMoneyCast;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Foundation\Observers\AuditLogObserver;
use Kezi\Purchase\Enums\Purchases\RequestForQuotationStatus;

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
 * @property-read int|null $lines_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation bidReceived()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation draft()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation sent()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereConvertedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereConvertedToPurchaseOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereCreatedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereExchangeRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereRfqDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereRfqNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereTaxTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereValidUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation whereVendorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RequestForQuotation withoutTrashed()
 * @method static \Kezi\Purchase\Database\Factories\RequestForQuotationFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[ObservedBy([AuditLogObserver::class])]
class RequestForQuotation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory(): \Kezi\Purchase\Database\Factories\RequestForQuotationFactory
    {
        return \Kezi\Purchase\Database\Factories\RequestForQuotationFactory::new();
    }

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

    /**
     * Boot the model and set up event listeners.
     */
    protected static function booted(): void
    {
        static::saving(function (self $rfq) {
            if ($rfq->relationLoaded('lines')) {
                $rfq->calculateTotals();
            }
        });
    }

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
        $currency = $this->currency;

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
