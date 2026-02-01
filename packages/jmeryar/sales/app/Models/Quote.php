<?php

namespace Jmeryar\Sales\Models;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Jmeryar\Foundation\Casts\BaseCurrencyMoneyCast;
use Jmeryar\Foundation\Casts\DocumentCurrencyMoneyCast;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Foundation\Observers\AuditLogObserver;
use Jmeryar\Sales\Enums\Sales\QuoteStatus;
use Jmeryar\Sales\Observers\QuoteObserver;

/**
 * Quote Model
 *
 * Represents a sales quotation - a pre-commitment document for price negotiation.
 * Quotes support versioning for revisions and can be converted to Sales Orders
 * or directly to Invoices.
 *
 * @property int $id
 * @property int $company_id
 * @property int $partner_id
 * @property int $currency_id
 * @property int|null $created_by_user_id
 * @property string|null $quote_number
 * @property Carbon $quote_date
 * @property Carbon $valid_until
 * @property QuoteStatus $status
 * @property int $version
 * @property int|null $previous_version_id
 * @property int|null $converted_to_sales_order_id
 * @property int|null $converted_to_invoice_id
 * @property Carbon|null $converted_at
 * @property float $exchange_rate
 * @property Money $subtotal
 * @property Money $tax_total
 * @property Money $discount_total
 * @property Money $total
 * @property Money|null $subtotal_company_currency
 * @property Money|null $tax_total_company_currency
 * @property Money|null $discount_total_company_currency
 * @property Money|null $total_company_currency
 * @property string|null $notes
 * @property string|null $terms_and_conditions
 * @property string|null $rejection_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Company $company
 * @property-read Partner $partner
 * @property-read Currency $currency
 * @property-read User|null $createdBy
 * @property-read Quote|null $previousVersion
 * @property-read SalesOrder|null $convertedToSalesOrder
 * @property-read Invoice|null $convertedToInvoice
 * @property-read Collection<int, QuoteLine> $lines
 * @property-read int|null $lines_count
 */
#[ObservedBy([QuoteObserver::class, AuditLogObserver::class])]
class Quote extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'partner_id',
        'currency_id',
        'created_by_user_id',
        'quote_number',
        'quote_date',
        'valid_until',
        'status',
        'version',
        'previous_version_id',
        'converted_to_sales_order_id',
        'converted_to_invoice_id',
        'converted_at',
        'exchange_rate',
        'subtotal',
        'tax_total',
        'discount_total',
        'total',
        'subtotal_company_currency',
        'tax_total_company_currency',
        'discount_total_company_currency',
        'total_company_currency',
        'notes',
        'terms_and_conditions',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quote_date' => 'date',
        'valid_until' => 'date',
        'status' => QuoteStatus::class,
        'version' => 'integer',
        'exchange_rate' => 'decimal:8',
        'converted_at' => 'datetime',
        'subtotal' => DocumentCurrencyMoneyCast::class,
        'tax_total' => DocumentCurrencyMoneyCast::class,
        'discount_total' => DocumentCurrencyMoneyCast::class,
        'total' => DocumentCurrencyMoneyCast::class,
        'subtotal_company_currency' => BaseCurrencyMoneyCast::class,
        'tax_total_company_currency' => BaseCurrencyMoneyCast::class,
        'discount_total_company_currency' => BaseCurrencyMoneyCast::class,
        'total_company_currency' => BaseCurrencyMoneyCast::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

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
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
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
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<QuoteLine, static>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(QuoteLine::class)->orderBy('line_order');
    }

    /**
     * @return BelongsTo<Quote, static>
     */
    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'previous_version_id');
    }

    /**
     * @return BelongsTo<SalesOrder, static>
     */
    public function convertedToSalesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'converted_to_sales_order_id');
    }

    /**
     * @return BelongsTo<Invoice, static>
     */
    public function convertedToInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_to_invoice_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope a query to only include draft quotes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeDraft($query)
    {
        return $query->where('status', QuoteStatus::Draft);
    }

    /**
     * Scope a query to only include sent quotes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeSent($query)
    {
        return $query->where('status', QuoteStatus::Sent);
    }

    /**
     * Scope a query to only include accepted quotes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', QuoteStatus::Accepted);
    }

    /**
     * Scope a query to only include expired quotes (validity passed and not yet marked).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now())
            ->whereIn('status', [QuoteStatus::Draft, QuoteStatus::Sent]);
    }

    /**
     * Scope a query to only include active quotes (not in final state).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', QuoteStatus::activeStatuses());
    }

    // =========================================================================
    // Business Logic Helpers
    // =========================================================================

    /**
     * Check if the quote can be edited.
     */
    public function isEditable(): bool
    {
        return $this->status->canBeEdited();
    }

    /**
     * Check if the quote can be converted to Sales Order or Invoice.
     */
    public function canBeConverted(): bool
    {
        return $this->status->canBeConverted() &&
            ! $this->converted_to_sales_order_id &&
            ! $this->converted_to_invoice_id;
    }

    /**
     * Check if the quote has expired based on validity date.
     */
    public function isExpired(): bool
    {
        return $this->valid_until->isPast() &&
            ! in_array($this->status, [QuoteStatus::Accepted, QuoteStatus::Converted]);
    }

    /**
     * Get the display reference for the quote.
     */
    public function getDisplayReference(): string
    {
        $reference = $this->quote_number ?? "Quote #{$this->id}";
        if ($this->version > 1) {
            $reference .= " (v{$this->version})";
        }

        return $reference;
    }

    /**
     * Calculate and update the total amounts for this quote.
     * Also calculates company currency totals using the exchange rate.
     */
    public function calculateTotals(): void
    {
        $currency = $this->currency ?? $this->currency()->first();
        $company = $this->company ?? $this->company()->first();

        if (! $currency || ! $company) {
            return;
        }

        $subtotal = Money::of(0, $currency->code);
        $taxTotal = Money::of(0, $currency->code);
        $discountTotal = Money::of(0, $currency->code);

        foreach ($this->lines as $line) {
            $subtotal = $subtotal->plus($line->subtotal);
            $taxTotal = $taxTotal->plus($line->tax_amount);
            $discountTotal = $discountTotal->plus($line->discount_amount);
        }

        $this->subtotal = $subtotal;
        $this->tax_total = $taxTotal;
        $this->discount_total = $discountTotal;
        $this->total = $subtotal->plus($taxTotal);

        // Calculate company currency totals
        $companyCurrency = $company->currency;
        $baseCurrencyCode = $companyCurrency?->code ?? 'IQD';
        $exchangeRate = $this->exchange_rate ?? 1.0;

        // If same currency, exchange rate is 1
        if ($companyCurrency && $currency->id === $companyCurrency->id) {
            $exchangeRate = 1.0;
            $this->exchange_rate = 1.0;
        }

        // Convert to company currency
        $this->subtotal_company_currency = Money::of(
            $subtotal->getAmount()->toFloat() * $exchangeRate,
            $baseCurrencyCode
        );
        $this->tax_total_company_currency = Money::of(
            $taxTotal->getAmount()->toFloat() * $exchangeRate,
            $baseCurrencyCode
        );
        $this->discount_total_company_currency = Money::of(
            $discountTotal->getAmount()->toFloat() * $exchangeRate,
            $baseCurrencyCode
        );
        $this->total_company_currency = Money::of(
            $this->total->getAmount()->toFloat() * $exchangeRate,
            $baseCurrencyCode
        );
    }

    /**
     * Get the total amount including tax.
     */
    public function getTotalWithTax(): Money
    {
        return $this->subtotal->plus($this->tax_total);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Jmeryar\Sales\Database\Factories\QuoteFactory
    {
        return \Jmeryar\Sales\Database\Factories\QuoteFactory::new();
    }
}
