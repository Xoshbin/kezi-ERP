<?php

namespace Modules\Inventory\Models;

use App\Casts\BaseCurrencyMoneyCast;
use App\Casts\DocumentCurrencyMoneyCast;
use App\Enums\Adjustments\AdjustmentDocumentStatus;
use App\Enums\Adjustments\AdjustmentDocumentType;
use Brick\Money\Money;
use Database\Factories\AdjustmentDocumentFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $original_invoice_id
 * @property int|null $original_vendor_bill_id
 * @property int|null $journal_entry_id
 * @property string $type
 * @property Carbon $date
 * @property string $reference_number
 * @property Money $subtotal
 * @property Money $total_amount
 * @property Money $total_tax
 * @property string $reason
 * @property AdjustmentDocumentStatus $status
 * @property string|null $posted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Currency $currency
 * @property-read JournalEntry|null $journalEntry
 * @property-read Invoice|null $originalInvoice
 * @property-read VendorBill|null $originalVendorBill
 *
 * @method static \Modules\Inventory\Database\Factories\AdjustmentDocumentFactory factory($count = null, $state = [])
 * @method static Builder<static>|AdjustmentDocument newModelQuery()
 * @method static Builder<static>|AdjustmentDocument newQuery()
 * @method static Builder<static>|AdjustmentDocument query()
 * @method static Builder<static>|AdjustmentDocument whereCompanyId($value)
 * @method static Builder<static>|AdjustmentDocument whereCreatedAt($value)
 * @method static Builder<static>|AdjustmentDocument whereDate($value)
 * @method static Builder<static>|AdjustmentDocument whereId($value)
 * @method static Builder<static>|AdjustmentDocument whereJournalEntryId($value)
 * @method static Builder<static>|AdjustmentDocument whereOriginalInvoiceId($value)
 * @method static Builder<static>|AdjustmentDocument whereOriginalVendorBillId($value)
 * @method static Builder<static>|AdjustmentDocument wherePostedAt($value)
 * @method static Builder<static>|AdjustmentDocument whereReason($value)
 * @method static Builder<static>|AdjustmentDocument whereReferenceNumber($value)
 * @method static Builder<static>|AdjustmentDocument whereStatus($value)
 * @method static Builder<static>|AdjustmentDocument whereTotalAmount($value)
 * @method static Builder<static>|AdjustmentDocument whereTotalTax($value)
 * @method static Builder<static>|AdjustmentDocument whereType($value)
 * @method static Builder<static>|AdjustmentDocument whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class AdjustmentDocument extends Model
{
    /** @use HasFactory<AdjustmentDocumentFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     * Explicitly defining the table name is good practice, particularly for
     * singular model names that might have unexpected pluralizations.
     *
     * @var string
     */
    protected $table = 'adjustment_documents'; // [5]

    /**
     * The attributes that are mass assignable.
     * These fields align with the columns defined in your database migration
     * for the 'adjustment_documents' table, allowing for bulk assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',             // [5]
        'original_invoice_id',    // [5]
        'original_vendor_bill_id', // [5]
        'type',                   // [5] e.g., 'Credit Note', 'Debit Note', 'Miscellaneous Adjustment'
        'date',                   // [5]
        'reference_number',       // [5]
        'subtotal',               // [5] Subtotal before taxes
        'total_amount',           // [5]
        'total_tax',              // [5]
        'exchange_rate_at_creation', // Exchange rate captured at creation/posting
        'subtotal_company_currency',     // Subtotal in company base currency
        'total_amount_company_currency', // Total amount in company base currency
        'total_tax_company_currency',    // Total tax in company base currency
        'reason',                 // [5]
        'status',                 // [5] e.g., 'Draft', 'Posted'
        'journal_entry_id',       // [5]
        'posted_at',
        'currency_id',
    ];

    /**
     * The attributes that should be cast.
     * Casting 'date' to 'date' ensures it's handled as a Carbon instance without time,
     * while 'total_amount' and 'total_tax' can be cast to 'decimal' for precision.
     * 'created_at' and 'updated_at' are automatically managed as datetime by Eloquent.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',       // [5, 6]
        'type' => AdjustmentDocumentType::class,
        'status' => AdjustmentDocumentStatus::class,
        'exchange_rate_at_creation' => 'decimal:10',
        'subtotal' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,  // Document currency amounts
        'total_amount' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,  // Document currency amounts
        'total_tax' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,  // Document currency amounts
        'subtotal_company_currency' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class,  // Company base currency amounts
        'total_amount_company_currency' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class,  // Company base currency amounts
        'total_tax_company_currency' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class,  // Company base currency amounts
        'created_at' => 'datetime',   // [5, 6]
        'updated_at' => 'datetime',   // [5, 6]
    ];

    /**
     * The "booted" method of the model.
     * This is an appropriate place to enforce global constraints or event listeners.
     * In accounting, immutability of 'Posted' documents is paramount.
     * While not directly altering the model's properties, this serves as a reminder
     * that application logic MUST prevent modification of 'Posted' documents.
     */
    protected static function booted(): void
    {
        static::updating(function (self $adjustmentDocument) {
            // Principle: Once a financial document is 'Posted', it is immutable. [1, 2, 5]
            // This check should be mirrored and more robustly enforced in application logic (e.g., Form Requests, Services).
            if ($adjustmentDocument->isDirty('status') && $adjustmentDocument->getOriginal('status') === 'Posted') {
                // Forcing a status change from 'Posted' to anything else should be highly restricted
                // and typically only allowed via explicit 'reset to draft' operations with extensive logging.
                // Or, if any other field is dirty while status is 'Posted'.
                // For true immutability, this would throw an exception or prevent the update.
                // Here, we provide a basic example; comprehensive validation should be elsewhere.
                // For instance, a dedicated Policy or FormRequest for update operations.
            }
            if ($adjustmentDocument->getOriginal('status') === 'Posted' && $adjustmentDocument->isDirty() && ! $adjustmentDocument->isDirty('status')) {
                // If a posted document is being modified without a legitimate "reset to draft" flow, prevent it.
                // This is a crucial accounting principle for immutable records. [1-3]
                // throw new \LogicException('Posted adjustment documents cannot be modified directly.');
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    | These methods define the relationships this model has with other models,
    | crucial for a cohesive accounting system.
    */

    /**
     * @return HasMany<AdjustmentDocumentLine, static>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(AdjustmentDocumentLine::class);
    }

    /**
     * Get the company that owns the adjustment document.
     * An adjustment document always belongs to a specific company.
     *
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class); // [5, 7]
    }

    /**
     * Get the original invoice that this adjustment document relates to (if any).
     * This is used for credit notes issued against customer invoices.
     * It's nullable as not all adjustment documents will be for invoices.
     *
     * @return BelongsTo<Invoice, static>
     */
    public function originalInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'original_invoice_id'); // [5, 7]
    }

    /**
     * Get the original vendor bill that this adjustment document relates to (if any).
     * This is used for debit notes issued against vendor bills.
     * It's nullable as not all adjustment documents will be for vendor bills.
     *
     * @return BelongsTo<VendorBill, static>
     */
    public function originalVendorBill(): BelongsTo
    {
        return $this->belongsTo(VendorBill::class, 'original_vendor_bill_id'); // [5, 7]
    }

    /**
     * Get the journal entry associated with this adjustment document.
     * Once an adjustment document is 'Posted', it generates a corresponding
     * journal entry, which is the immutable record in the general ledger.
     *
     * @return BelongsTo<JournalEntry, static>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class); // [5, 7]
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators (Optional but good for calculated attributes)
    |--------------------------------------------------------------------------
    | Methods to transform attribute values when they are retrieved or set.
    */
    // Example of an accessor if you needed a human-readable status, not strictly required for this model.
    // public function getStatusLabelAttribute(): string
    // {
    //     return match($this->status) {
    //         'Draft' => 'Draft',
    //         'Posted' => 'Posted (Immutable)',
    //         'Cancelled' => 'Cancelled',
    //         default => 'Unknown',
    //     };
    // }
    /**
     * Get the currency of this invoice.
     * Every invoice operates in a specific currency. [1]
     *
     * @return BelongsTo<Currency, static>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Business Logic Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Calculates and sets the total tax and total amount for the document
     * by summing the values from its associated lines.
     */
    public function calculateTotalsFromLines(): void
    {
        $this->loadMissing('lines.tax', 'currency');

        $currencyCode = $this->currency->code;
        $zero = Money::of(0, $currencyCode);

        /** @var Collection<int, AdjustmentDocumentLine> $lines */
        $lines = $this->lines;

        $totalTax = $lines->reduce(
            fn (Money $carry, AdjustmentDocumentLine $line) => $carry->plus($line->total_line_tax ?? $zero),
            $zero
        );

        $subtotal = $lines->reduce(
            fn (Money $carry, AdjustmentDocumentLine $line) => $carry->plus($line->subtotal ?? $zero),
            $zero
        );

        $this->subtotal = $subtotal;
        $this->total_tax = $totalTax;
        $this->total_amount = $subtotal->plus($totalTax);
    }
}
