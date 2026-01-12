<?php

namespace Modules\Sales\Models;

use App\Models\Company;
use Brick\Money\Money;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Accounting\Models\FiscalPosition;
use Modules\Accounting\Models\JournalEntry;
use Modules\Foundation\Enums\Incoterm;
use Modules\Foundation\Models\Concerns\HasDocumentAttachments;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Foundation\Models\PaymentTerm;
use Modules\Inventory\Models\AdjustmentDocument;
use Modules\Payment\Enums\PaymentInstallments\InstallmentStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentDocumentLink;
use Modules\Payment\Models\PaymentInstallment;
use Modules\Sales\Enums\Sales\InvoiceStatus;

/**
 * Class Invoice
 *
 * @property int $id
 * @property int $company_id
 * @property int $customer_id
 * @property int|null $sales_order_id
 * @property int $currency_id
 * @property int|null $journal_entry_id
 * @property int|null $fiscal_position_id
 * @property string|null $invoice_number
 * @property Carbon $invoice_date
 * @property Carbon $due_date
 * @property InvoiceStatus $status
 * @property Incoterm|null $incoterm
 * @property Money $total_amount
 * @property Money $total_tax
 * @property Carbon|null $posted_at
 * @property array<array-key, mixed>|null $reset_to_draft_log
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Currency $currency
 * @property-read Partner $customer
 * @property-read SalesOrder|null $salesOrder
 * @property-read FiscalPosition|null $fiscalPosition
 * @property-read string $full_reference
 * @property-read Collection<int, InvoiceLine> $invoiceLines
 * @property-read int|null $invoice_lines_count
 * @property-read JournalEntry|null $journalEntry
 *
 * @method static Builder<static>|Invoice draft()
 * @method static \Modules\Sales\Database\Factories\InvoiceFactory factory($count = null, $state = [])
 * @method static Builder<static>|Invoice newModelQuery()
 * @method static Builder<static>|Invoice newQuery()
 * @method static Builder<static>|Invoice posted()
 * @method static Builder<static>|Invoice query()
 * @method static Builder<static>|Invoice whereCompanyId($value)
 * @method static Builder<static>|Invoice whereCreatedAt($value)
 * @method static Builder<static>|Invoice whereCurrencyId($value)
 * @method static Builder<static>|Invoice whereCustomerId($value)
 * @method static Builder<static>|Invoice whereDueDate($value)
 * @method static Builder<static>|Invoice whereFiscalPositionId($value)
 * @method static Builder<static>|Invoice whereId($value)
 * @method static Builder<static>|Invoice whereInvoiceDate($value)
 * @method static Builder<static>|Invoice whereInvoiceNumber($value)
 * @method static Builder<static>|Invoice whereJournalEntryId($value)
 * @method static Builder<static>|Invoice wherePostedAt($value)
 * @method static Builder<static>|Invoice whereResetToDraftLog($value)
 * @method static Builder<static>|Invoice whereStatus($value)
 * @method static Builder<static>|Invoice whereTotalAmount($value)
 * @method static Builder<static>|Invoice whereTotalTax($value)
 * @method static Builder<static>|Invoice whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
#[ObservedBy([\Modules\Foundation\Observers\AuditLogObserver::class])]
class Invoice extends Model
{
    use HasDocumentAttachments;
    use HasFactory;
    use \Modules\Foundation\Traits\HasPaymentState;

    /**
     * The table associated with the model.
     * The sources indicate this model maps to the 'invoices' table. [1, 5]
     *
     * @var string
     */
    protected $table = 'invoices';

    /**
     * The attributes that are mass assignable.
     * These fields can be filled via mass assignment. Fields like 'invoice_number',
     * 'status', 'journal_entry_id', 'posted_at', and 'reset_to_draft_log' are
     * typically managed internally by the application's logic upon confirmation
     * or posting, rather than being directly mass-assigned from user input. [1, 6]
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'customer_id',
        'sales_order_id',
        'currency_id',
        'exchange_rate_at_creation',
        'journal_entry_id',
        'fiscal_position_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'payment_term_id',
        'status',
        'incoterm',
        'total_amount',
        'total_tax',
        'total_amount_company_currency',
        'total_tax_company_currency',
        'posted_at',
        'inter_company_source_type',
        'reset_to_draft_log',
        'dunning_level_id',
        'last_dunning_date',
        'next_dunning_date',
        'source_invoice_id',
    ];

    /**
     * The attributes that should be cast.
     * Ensures dates are Carbon instances and JSON fields are properly handled. [1]
     *
     * @var array<string, string>
     */
    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'status' => InvoiceStatus::class,
        'incoterm' => Incoterm::class,
        'exchange_rate_at_creation' => 'decimal:10',
        'total_amount' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'total_tax' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'total_amount_company_currency' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'total_tax_company_currency' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'reset_to_draft_log' => 'json', // Store as JSON/Text as per source [1]
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'posted_at' => 'datetime',
        'last_dunning_date' => 'datetime',
        'next_dunning_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    | These methods define the relationships this Invoice model has with other
    | entities in the accounting system, crucial for data integrity and navigation.
    */

    public function sourceInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'source_invoice_id');
    }

    public function generatedDebitNotes(): HasMany
    {
        return $this->hasMany(Invoice::class, 'source_invoice_id');
    }

    /**
     * Get the company that owns this invoice.
     * An invoice is always issued by a specific company. [1]
     */
    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the customer (partner) associated with this invoice.
     * The customer is the entity to whom the invoice is issued. [1]
     */
    /**
     * @return BelongsTo<Partner, static>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * @return BelongsTo<SalesOrder, static>
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Get the currency of this invoice.
     * Every invoice operates in a specific currency. [1]
     */
    /**
     * @return BelongsTo<Currency, static>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the journal entry associated with this invoice once it is posted.
     * This link is vital for the immutability of financial records. [1]
     */
    /**
     * @return BelongsTo<JournalEntry, static>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Get the line items for this invoice.
     * An invoice typically consists of multiple product or service lines. [1]
     */
    /**
     * @return HasMany<InvoiceLine, static>
     */
    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * Get the fiscal position applied to this invoice.
     * Fiscal positions can automatically adapt taxes and accounts based on specific rules. [4, 7]
     */
    /**
     * @return BelongsTo<FiscalPosition, static>
     */
    public function fiscalPosition(): BelongsTo
    {
        return $this->belongsTo(FiscalPosition::class);
    }

    /**
     * @return BelongsTo<\Modules\Accounting\Models\DunningLevel, static>
     */
    public function dunningLevel(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\DunningLevel::class);
    }

    /**
     * Scope a query to only include invoices that are overdue.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', InvoiceStatus::Posted)
            ->where('due_date', '<', Carbon::today());
    }

    /**
     * Get the Payments that are applied to this Invoice.
     * An invoice can be paid by multiple payments, and a single payment
     * can potentially pay multiple invoices, creating a many-to-many relationship.
     */
    /**
     * @return BelongsToMany<Payment, static>
     */
    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class, 'payment_document_links', 'invoice_id', 'payment_id')
            ->withPivot('amount_applied');
    }

    /**
     * Get the direct PaymentDocumentLink records for this invoice.
     * This provides access to the raw pivot data for multi-currency payment calculations.
     */
    /**
     * @return HasMany<PaymentDocumentLink, static>
     */
    public function paymentDocumentLinks(): HasMany
    {
        return $this->hasMany(PaymentDocumentLink::class, 'invoice_id');
    }

    /**
     * Get the PaymentTerm for this invoice.
     */
    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    /**
     * Get the PaymentInstallments for this invoice.
     */
    public function paymentInstallments(): HasMany
    {
        return $this->hasMany(PaymentInstallment::class, 'installment_id')
            ->where('installment_type', self::class)
            ->orderBy('sequence');
    }

    /**
     * Get the Adjustment Documents (credit notes, etc.) that relate to this Invoice.
     * These are used for corrections, reversals, and adjustments to posted invoices.
     */
    /**
     * @return HasMany<AdjustmentDocument, static>
     */
    public function adjustmentDocuments(): HasMany
    {
        return $this->hasMany(AdjustmentDocument::class, 'original_invoice_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes (Example)
    |--------------------------------------------------------------------------
    */
    /**
     * Scope a query to only include posted invoices.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopePosted($query)
    {
        return $query->whereIn('status', [InvoiceStatus::Posted, InvoiceStatus::Paid]);
    }

    /**
     * Scope a query to only include draft invoices.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeDraft($query)
    {
        return $query->where('status', InvoiceStatus::Draft);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators (Example)
    |--------------------------------------------------------------------------
    */

    /**
     * Get the full invoice reference, combining number and date for display.
     */
    public function getFullReferenceAttribute(): string
    {
        return $this->invoice_number.' - '.$this->invoice_date->format('Y-m-d');
    }

    /**
     * Generate payment installments based on payment terms.
     * This should be called when the invoice is posted.
     */
    public function generatePaymentInstallments(): void
    {
        // Clear existing installments
        $this->paymentInstallments()->delete();

        $this->loadMissing('paymentTerm');

        if (! $this->paymentTerm instanceof PaymentTerm) {
            // No payment terms, create single installment with due date
            PaymentInstallment::create([
                'company_id' => $this->company_id,
                'installment_type' => self::class,
                'installment_id' => $this->id,
                'sequence' => 1,
                'due_date' => $this->due_date,
                'amount' => $this->total_amount,
                'status' => InstallmentStatus::Pending,
            ]);

            return;
        }

        $installments = $this->paymentTerm->calculateInstallments($this->invoice_date, $this->total_amount);

        foreach ($installments as $index => $installment) {
            PaymentInstallment::create([
                'company_id' => $this->company_id,
                'installment_type' => self::class,
                'installment_id' => $this->id,
                'sequence' => $index + 1,
                'due_date' => $installment['due_date'],
                'amount' => $installment['amount'],
                'status' => InstallmentStatus::Pending,
            ]);
        }
    }

    public function calculateTotalsFromLines(): void
    {
        $this->loadMissing('invoiceLines.tax', 'currency');

        $currencyCode = $this->currency->code;
        $zero = Money::of(0, $currencyCode);

        $totalTax = $this->invoiceLines->reduce(
            fn (Money $carry, InvoiceLine $line) => $carry->plus($line->total_line_tax ?? Money::of(0, $currencyCode)),
            Money::of(0, $currencyCode)
        );

        $subtotal = $this->invoiceLines->reduce(
            fn (Money $carry, InvoiceLine $line) => $carry->plus($line->subtotal ?? Money::of(0, $currencyCode)),
            Money::of(0, $currencyCode)
        );

        $this->total_tax = $totalTax;
        $this->total_amount = $subtotal->plus($totalTax);
    }

    protected static function newFactory(): \Modules\Sales\Database\Factories\InvoiceFactory
    {
        return \Modules\Sales\Database\Factories\InvoiceFactory::new();
    }
}
