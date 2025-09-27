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
 * Sales Order Line Model
 *
 * Represents a line item in a sales order, containing product details,
 * quantities, and pricing information.
 *
 * @property int $id
 * @property int $sales_order_id
 * @property int $product_id
 * @property int|null $tax_id
 * @property string $description
 * @property float $quantity
 * @property float $quantity_delivered
 * @property float $quantity_invoiced
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
 * @property-read SalesOrder $salesOrder
 * @property-read Product $product
 * @property-read Tax|null $tax
 */
#[ObservedBy([AuditLogObserver::class])]
class SalesOrderLine extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sales_order_id',
        'product_id',
        'tax_id',
        'description',
        'quantity',
        'quantity_delivered',
        'quantity_invoiced',
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
        'quantity_delivered' => 'float',
        'quantity_invoiced' => 'float',
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
     * @return BelongsTo<SalesOrder, static>
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * @return BelongsTo<Product, static>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Tax, static>
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Calculate line totals based on quantity, unit price, and tax.
     */
    public function calculateTotals(): void
    {
        $currency = $this->salesOrder->currency ?? $this->salesOrder()->first()->currency;

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
     * Get the remaining quantity to be delivered.
     */
    public function getRemainingToDeliver(): float
    {
        return max(0, $this->quantity - $this->quantity_delivered);
    }

    /**
     * Get the remaining quantity to be invoiced.
     */
    public function getRemainingToInvoice(): float
    {
        return max(0, $this->quantity - $this->quantity_invoiced);
    }

    /**
     * Check if this line is fully delivered.
     */
    public function isFullyDelivered(): bool
    {
        return $this->quantity_delivered >= $this->quantity;
    }

    /**
     * Check if this line is fully invoiced.
     */
    public function isFullyInvoiced(): bool
    {
        return $this->quantity_invoiced >= $this->quantity;
    }

    /**
     * Get the delivery progress as a percentage.
     */
    public function getDeliveryProgress(): float
    {
        if ($this->quantity == 0) {
            return 0;
        }
        
        return ($this->quantity_delivered / $this->quantity) * 100;
    }

    /**
     * Get the invoicing progress as a percentage.
     */
    public function getInvoicingProgress(): float
    {
        if ($this->quantity == 0) {
            return 0;
        }
        
        return ($this->quantity_invoiced / $this->quantity) * 100;
    }

    /**
     * Get the value of delivered quantity.
     */
    public function getDeliveredValue(): Money
    {
        return $this->unit_price->multipliedBy($this->quantity_delivered);
    }

    /**
     * Get the value of invoiced quantity.
     */
    public function getInvoicedValue(): Money
    {
        return $this->unit_price->multipliedBy($this->quantity_invoiced);
    }

    /**
     * Get the remaining value to be delivered.
     */
    public function getRemainingDeliveryValue(): Money
    {
        return $this->unit_price->multipliedBy($this->getRemainingToDeliver());
    }

    /**
     * Get the remaining value to be invoiced.
     */
    public function getRemainingInvoiceValue(): Money
    {
        return $this->unit_price->multipliedBy($this->getRemainingToInvoice());
    }

    /**
     * Update the delivered quantity and recalculate if needed.
     */
    public function updateDeliveredQuantity(float $deliveredQuantity): void
    {
        $this->quantity_delivered = min($deliveredQuantity, $this->quantity);
        $this->save();
    }

    /**
     * Update the invoiced quantity and recalculate if needed.
     */
    public function updateInvoicedQuantity(float $invoicedQuantity): void
    {
        $this->quantity_invoiced = min($invoicedQuantity, $this->quantity);
        $this->save();
    }

    /**
     * Check if this line can be delivered.
     */
    public function canBeDelivered(): bool
    {
        return $this->getRemainingToDeliver() > 0;
    }

    /**
     * Check if this line can be invoiced.
     */
    public function canBeInvoiced(): bool
    {
        return $this->getRemainingToInvoice() > 0;
    }
}
