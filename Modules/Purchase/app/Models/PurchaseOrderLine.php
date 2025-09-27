<?php

namespace App\Models;

use App\Casts\BaseCurrencyMoneyCast;
use App\Casts\DocumentCurrencyMoneyCast;
use App\Observers\AuditLogObserver;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Purchase Order Line Model
 *
 * Represents a line item in a purchase order, containing product details,
 * quantities, and pricing information.
 *
 * @property int $id
 * @property int $purchase_order_id
 * @property int $product_id
 * @property int|null $tax_id
 * @property string $description
 * @property float $quantity
 * @property float $quantity_received
 * @property Money $unit_price
 * @property Money $subtotal
 * @property Money $total_line_tax
 * @property Money $total
 * @property Money|null $unit_price_company_currency
 * @property Money|null $subtotal_company_currency
 * @property Money|null $total_line_tax_company_currency
 * @property Money|null $total_company_currency
 * @property Carbon|null $expected_delivery_date
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PurchaseOrder $purchaseOrder
 * @property-read Product $product
 * @property-read Tax|null $tax
 */
#[ObservedBy([AuditLogObserver::class])]
class PurchaseOrderLine extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'tax_id',
        'description',
        'quantity',
        'quantity_received',
        'unit_price',
        'subtotal',
        'total_line_tax',
        'total',
        'unit_price_company_currency',
        'subtotal_company_currency',
        'total_line_tax_company_currency',
        'total_company_currency',
        'expected_delivery_date',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quantity' => 'float',
        'quantity_received' => 'float',
        'unit_price' => DocumentCurrencyMoneyCast::class,
        'subtotal' => DocumentCurrencyMoneyCast::class,
        'total_line_tax' => DocumentCurrencyMoneyCast::class,
        'total' => DocumentCurrencyMoneyCast::class,
        'unit_price_company_currency' => BaseCurrencyMoneyCast::class,
        'subtotal_company_currency' => BaseCurrencyMoneyCast::class,
        'total_line_tax_company_currency' => BaseCurrencyMoneyCast::class,
        'total_company_currency' => BaseCurrencyMoneyCast::class,
        'expected_delivery_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The relationships that should always be loaded.
     * Eager-loading the `purchaseOrder.currency` relationship is critical because the `DocumentCurrencyMoneyCast`
     * for monetary fields on this model depends on the currency context provided by the purchase order.
     *
     * @var list<string>
     */
    protected $with = ['purchaseOrder.currency'];

    /**
     * Get the Purchase Order that owns this line.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the Product for this line.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the Tax for this line.
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Get the document currency for this purchase order line.
     * Required by DocumentCurrencyMoneyCast.
     */
    public function getDocumentCurrency(): Currency
    {
        return $this->purchaseOrder->currency;
    }

    /**
     * Calculate line totals based on quantity, unit price, and tax.
     */
    public function calculateTotals(): void
    {
        $currency = $this->purchaseOrder->currency ?? $this->purchaseOrder()->first()->currency;

        // Calculate subtotal
        $this->subtotal = $this->unit_price->multipliedBy($this->quantity);

        // Calculate tax
        if ($this->tax_id && $this->tax) {
            $taxRate = $this->tax->rate / 100;
            $this->total_line_tax = $this->subtotal->multipliedBy($taxRate);
        } else {
            $this->total_line_tax = Money::of(0, $currency->code);
        }

        // Calculate total
        $this->total = $this->subtotal->plus($this->total_line_tax);
    }

    /**
     * Get the remaining quantity to be received.
     */
    public function getRemainingQuantity(): float
    {
        return max(0, $this->quantity - $this->quantity_received);
    }

    /**
     * Check if this line is fully received.
     */
    public function isFullyReceived(): bool
    {
        return $this->quantity_received >= $this->quantity;
    }

    /**
     * Check if this line is partially received.
     */
    public function isPartiallyReceived(): bool
    {
        return $this->quantity_received > 0 && $this->quantity_received < $this->quantity;
    }

    /**
     * Update the received quantity for this line.
     */
    public function updateReceivedQuantity(float $additionalQuantity): void
    {
        $this->quantity_received = min(
            $this->quantity,
            $this->quantity_received + $additionalQuantity
        );
    }

    /**
     * Get the unit price in company currency.
     * Falls back to converting from document currency if company currency amount is not set.
     */
    public function getUnitPriceInCompanyCurrency(): Money
    {
        if ($this->unit_price_company_currency) {
            return $this->unit_price_company_currency;
        }

        // If no company currency amount is stored, convert using exchange rate
        $purchaseOrder = $this->purchaseOrder;
        $exchangeRate = $purchaseOrder->exchange_rate_at_creation ?? 1.0;

        if ($purchaseOrder->currency_id === $purchaseOrder->company->currency_id) {
            return $this->unit_price;
        }

        // Convert to company currency
        $companyCurrency = $purchaseOrder->company->currency;
        return Money::of(
            $this->unit_price->getAmount()->toFloat() * $exchangeRate,
            $companyCurrency->code
        );
    }

    /**
     * Get the total cost for this line in company currency.
     */
    public function getTotalInCompanyCurrency(): Money
    {
        if ($this->total_company_currency) {
            return $this->total_company_currency;
        }

        return $this->getUnitPriceInCompanyCurrency()->multipliedBy($this->quantity);
    }
}
