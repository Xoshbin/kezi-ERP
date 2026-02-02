<?php

namespace Kezi\Sales\Models;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Kezi\Foundation\Enums\Incoterm;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Sales\Enums\Sales\SalesOrderStatus;

/**
 * Sales Order Model
 *
 * Represents a sales order - the central document for sales as described
 * in the sales plan. Sales orders serve as the "single source of truth"
 * for pricing and are used for revenue determination when goods are delivered.
 *
 * @property int $id
 * @property int $company_id
 * @property int $customer_id
 * @property int $currency_id
 * @property int $created_by_user_id
 * @property string|null $so_number
 * @property SalesOrderStatus $status
 * @property Incoterm|null $incoterm
 * @property string|null $reference
 * @property Carbon $so_date
 * @property Carbon|null $expected_delivery_date
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $cancelled_at
 * @property float|null $exchange_rate_at_creation
 * @property Money $total_amount
 * @property Money $total_tax
 * @property Money|null $total_amount_company_currency
 * @property Money|null $total_tax_company_currency
 * @property string|null $notes
 * @property string|null $terms_and_conditions
 * @property int|null $delivery_location_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Partner $customer
 * @property-read Currency $currency
 * @property-read User $createdByUser
 * @property-read StockLocation|null $deliveryLocation
 * @property-read Collection<int, SalesOrderLine> $lines
 * @property-read int|null $lines_count
 * @property-read Collection<int, Invoice> $invoices
 * @property-read int|null $invoices_count
 */
#[ObservedBy([\Kezi\Foundation\Observers\AuditLogObserver::class])]
class SalesOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'customer_id',
        'currency_id',
        'created_by_user_id',
        'so_number',
        'status',
        'reference',
        'so_date',
        'expected_delivery_date',
        'confirmed_at',
        'cancelled_at',
        'exchange_rate_at_creation',
        'total_amount',
        'total_tax',
        'total_amount_company_currency',
        'total_tax_company_currency',
        'notes',
        'terms_and_conditions',
        'delivery_location_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'status' => SalesOrderStatus::class,
        'incoterm' => Incoterm::class,
        'so_date' => 'date',
        'expected_delivery_date' => 'date',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'exchange_rate_at_creation' => 'decimal:6',
        'total_amount' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'total_tax' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'total_amount_company_currency' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'total_tax_company_currency' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Partner, static>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'customer_id');
    }

    /**
     * @return BelongsTo<Currency, static>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * @return BelongsTo<User, static>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return BelongsTo<StockLocation, static>
     */
    public function deliveryLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'delivery_location_id');
    }

    /**
     * @return HasMany<SalesOrderLine, static>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    /**
     * @return HasMany<Invoice, static>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'sales_order_id');
    }

    /**
     * Calculate and update the total amounts for this sales order.
     *
     * Also calculates company currency totals using the exchange rate.
     */
    public function calculateTotals(): void
    {
        $currency = $this->currency ?? $this->currency()->first();
        $company = $this->company ?? $this->company()->first();

        $totalAmount = Money::of(0, $currency->code);
        $totalTax = Money::of(0, $currency->code);

        foreach ($this->lines as $line) {
            $totalAmount = $totalAmount->plus($line->subtotal);
            $totalTax = $totalTax->plus($line->total_line_tax);
        }

        $this->total_amount = $totalAmount;
        $this->total_tax = $totalTax;

        // Calculate company currency totals
        $baseCurrencyCode = $company->currency->code ?? 'IQD';
        $exchangeRate = $this->exchange_rate_at_creation ?? 1.0;

        // If same currency, exchange rate is 1
        if ($currency->id === $company->currency_id) {
            $exchangeRate = 1.0;
            $this->exchange_rate_at_creation = 1.0;
        }

        // Convert to company currency
        $totalAmountCompany = Money::of(
            $totalAmount->getAmount()->toFloat() * $exchangeRate,
            $baseCurrencyCode
        );
        $totalTaxCompany = Money::of(
            $totalTax->getAmount()->toFloat() * $exchangeRate,
            $baseCurrencyCode
        );

        $this->total_amount_company_currency = $totalAmountCompany;
        $this->total_tax_company_currency = $totalTaxCompany;
    }

    /**
     * Check if the sales order can be confirmed.
     */
    public function canBeConfirmed(): bool
    {
        return $this->status->canBeConfirmed();
    }

    /**
     * Check if the sales order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    /**
     * Check if goods can be delivered against this sales order.
     */
    public function canDeliverGoods(): bool
    {
        return $this->status->canDeliverGoods();
    }

    /**
     * Check if customer invoices can be created against this sales order.
     */
    public function canCreateInvoice(): bool
    {
        // First check if status allows invoice creation
        if (! $this->status->canCreateInvoice()) {
            return false;
        }

        // Then check if invoices already exist for this SO
        return ! $this->hasInvoices();
    }

    /**
     * Check if this sales order has any associated invoices.
     */
    public function hasInvoices(): bool
    {
        return $this->invoices()->exists();
    }

    /**
     * Get the total amount including tax.
     */
    public function getTotalWithTax(): Money
    {
        return $this->total_amount->plus($this->total_tax);
    }

    /**
     * Get the remaining amount to be invoiced.
     */
    public function getRemainingToInvoice(): Money
    {
        $totalInvoiced = Money::of(0, $this->currency->code);

        foreach ($this->invoices as $invoice) {
            $totalInvoiced = $totalInvoiced->plus($invoice->total_amount);
        }

        return $this->total_amount->minus($totalInvoiced);
    }

    /**
     * Get the remaining amount to be delivered.
     */
    public function getRemainingToDeliver(): Money
    {
        $totalDelivered = Money::of(0, $this->currency->code);

        foreach ($this->lines as $line) {
            $deliveredValue = $line->unit_price->multipliedBy($line->quantity_delivered);
            $totalDelivered = $totalDelivered->plus($deliveredValue);
        }

        return $this->total_amount->minus($totalDelivered);
    }

    /**
     * Check if the sales order is fully delivered.
     */
    public function isFullyDelivered(): bool
    {
        foreach ($this->lines as $line) {
            if ($line->quantity_delivered < $line->quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the sales order is fully invoiced.
     */
    public function isFullyInvoiced(): bool
    {
        foreach ($this->lines as $line) {
            if ($line->quantity_invoiced < $line->quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the delivery progress as a percentage.
     */
    public function getDeliveryProgress(): float
    {
        $totalQuantity = $this->lines->sum('quantity');
        $deliveredQuantity = $this->lines->sum('quantity_delivered');

        if ($totalQuantity == 0) {
            return 0;
        }

        return ($deliveredQuantity / $totalQuantity) * 100;
    }

    /**
     * Get the invoicing progress as a percentage.
     */
    public function getInvoicingProgress(): float
    {
        $totalQuantity = $this->lines->sum('quantity');
        $invoicedQuantity = $this->lines->sum('quantity_invoiced');

        if ($totalQuantity == 0) {
            return 0;
        }

        return ($invoicedQuantity / $totalQuantity) * 100;
    }

    protected static function newFactory(): \Kezi\Sales\Database\Factories\SalesOrderFactory
    {
        return \Kezi\Sales\Database\Factories\SalesOrderFactory::new();
    }
}
