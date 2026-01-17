<?php

namespace Modules\Purchase\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Accounting\Models\Tax;
use Modules\Foundation\Casts\DocumentCurrencyMoneyCast;
use Modules\Foundation\Observers\AuditLogObserver;
use Modules\Product\Models\Product;

/**
 * RequestForQuotationLine Model
 *
 * Represents a line item in an RFQ.
 *
 * @property int $id
 * @property int $rfq_id
 * @property int|null $product_id
 * @property int|null $tax_id
 * @property string $description
 * @property float $quantity
 * @property string|null $unit
 * @property Money $unit_price
 * @property Money $subtotal
 * @property Money $tax_amount
 * @property Money $total
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read RequestForQuotation $rfq
 * @property-read Product|null $product
 * @property-read Tax|null $tax
 */
#[ObservedBy([AuditLogObserver::class])]
class RequestForQuotationLine extends Model
{
    use HasFactory;

    protected static function newFactory(): \Modules\Purchase\Database\Factories\RequestForQuotationLineFactory
    {
        return \Modules\Purchase\Database\Factories\RequestForQuotationLineFactory::new();
    }

    protected $fillable = [
        'rfq_id',
        'product_id',
        'tax_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'subtotal',
        'tax_amount',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => DocumentCurrencyMoneyCast::class,
        'subtotal' => DocumentCurrencyMoneyCast::class,
        'tax_amount' => DocumentCurrencyMoneyCast::class,
        'total' => DocumentCurrencyMoneyCast::class,
    ];

    protected $with = ['rfq.currency'];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Calculate line totals based on quantity, unit price, and tax.
     */
    public function calculateTotals(): void
    {
        $currencyCode = $this->rfq?->currency?->code ?? $this->rfq()->first()->currency->code;

        // Calculate subtotal
        $this->subtotal = $this->unit_price->multipliedBy($this->quantity, \Brick\Math\RoundingMode::HALF_UP);

        // Calculate tax
        if ($this->tax_id) {
            $tax = $this->tax ?? $this->tax()->first();
            if ($tax) {
                $taxRate = $tax->rate / 100;
                $this->tax_amount = $this->subtotal->multipliedBy((string) $taxRate, \Brick\Math\RoundingMode::HALF_UP);
            } else {
                $this->tax_amount = Money::of(0, $currencyCode);
            }
        } else {
            $this->tax_amount = Money::of(0, $currencyCode);
        }

        // Calculate total
        $this->total = $this->subtotal->plus($this->tax_amount);
    }
}
