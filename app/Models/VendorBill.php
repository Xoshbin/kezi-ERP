<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Observers\AuditLogObserver;
use App\Observers\VendorBillObserver;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Purchases\VendorBillStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// As a fundamental principle of accounting data integrity,
// 'posted' financial records, such as Vendor Bills, must be **immutable** [1-3].
// This means they should **not be directly deletable or alterable** through the user interface [1, 2].
// Instead, any corrections must be made via new, offsetting transactions (contra-entries/reversals),
// such as credit notes or debit notes [1-3].
// Therefore, the SoftDeletes trait is **intentionally omitted** for the VendorBill model
// to uphold auditability and prevent accidental data loss for historical financial records.
/**
 * @property int $id
 * @property int $company_id
 * @property int $vendor_id
 * @property int $currency_id
 * @property int|null $journal_entry_id
 * @property string $bill_reference
 * @property \Illuminate\Support\Carbon $bill_date
 * @property \Illuminate\Support\Carbon $accounting_date
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property string $status
 * @property \Brick\Money\Money $total_amount
 * @property \Brick\Money\Money $total_tax
 * @property \Illuminate\Support\Carbon|null $posted_at
 * @property array<array-key, mixed>|null $reset_to_draft_log
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Currency $currency
 * @property-read \App\Models\JournalEntry|null $journalEntry
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VendorBillLine> $lines
 * @property-read int|null $lines_count
 * @property-read \App\Models\Partner $vendor
 * @method static \Database\Factories\VendorBillFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill posted()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill whereAccountingDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill whereBillDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill whereBillReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill wherePostedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill whereResetToDraftLog($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill whereTotalTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VendorBill whereVendorId($value)
 * @mixin \Eloquent
 */

#[ObservedBy([AuditLogObserver::class, VendorBillObserver::class])]
class VendorBill extends Model
{
    use HasFactory;

    /**
     * The database table associated with the model.
     * Explicitly setting the table name for clarity and adherence to conventional naming.
     *
     * @var string
     */
    protected $table = 'vendor_bills';

    /**
     * The attributes that are mass assignable.
     * These fields are typically filled from user input or automated processes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',           // Foreign key to the Company model for multi-company support .
        'vendor_id',            // Foreign key to the Partner model, representing the supplier .
        'bill_date',            // The date the vendor bill was issued by the supplier .
        'accounting_date',      // The date the bill is recognized in the company's books .
        'due_date',             // The date by which the payment is due .
        'bill_reference',       // The vendor's reference number; **assigned only upon 'confirmation' or 'posting'**
        // to ensure a clean, unbroken sequence of official documents [4-6].
        'status',               // Current status: e.g., 'Draft', 'Posted', 'Paid', 'Cancelled' .
        // A 'Draft' bill can be modified/deleted, but 'Posted' cannot .
        'currency_id',          // Foreign key to the Currency model, specifying the bill's currency .
        'total_amount',         // The total amount of the vendor bill, including taxes .
        'total_tax',            // The total tax amount on the vendor bill .
        'journal_entry_id',     // Nullable foreign key to journal_entries.id, linking to the immutable
        // financial transaction once the bill is posted .
        'posted_at',            // Nullable timestamp indicating when the vendor bill was confirmed/posted .
        'reset_to_draft_log',   // JSON/Text field to log instances where a 'Posted' bill was
        // **reset to 'Draft' for modification**, crucial for maintaining an audit trail .
    ];

    /**
     * The attributes that should be cast to native types.
     * Essential for data integrity, especially for monetary values and dates.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'bill_date'          => 'date',       // Cast to date for consistency .
        'accounting_date'    => 'date',       // Cast to date for consistency .
        'due_date'           => 'date',       // Cast to date for consistency .
        'status'             => VendorBillStatus::class,
        'total_amount'       => MoneyCast::class,  // Crucial for financial precision, ensures two decimal places .
        'total_tax'          => MoneyCast::class,  // Crucial for financial precision .
        'posted_at'          => 'datetime',   // Records the exact time of posting for audit .
        'reset_to_draft_log' => 'json',       // Stores audit log as JSON .
        'created_at'         => 'datetime',   // Automatically managed by Eloquent.
        'updated_at'         => 'datetime',   // Automatically managed by Eloquent.
    ];

     protected static function booted(): void
    {
        static::saving(function (self $vendorBill) {
            if ($vendorBill->relationLoaded('lines')) {
                $vendorBill->calculateTotalsFromLines();
            }
        });
    }

    public function calculateTotalsFromLines(): void
    {
        $this->loadMissing('lines', 'currency');

        $currencyCode = $this->currency->code;
        $zero = \Brick\Money\Money::of(0, $currencyCode);

        $totalTax = $this->lines->reduce(
            fn (\Brick\Money\Money $carry, VendorBillLine $line) => $carry->plus($line->total_line_tax),
            $zero
        );

        $subtotal = $this->lines->reduce(
            fn (\Brick\Money\Money $carry, VendorBillLine $line) => $carry->plus($line->subtotal),
            $zero
        );

        $this->total_tax = $totalTax;
        $this->total_amount = $subtotal->plus($totalTax);
    }

    /**
     * Get the Company that owns the Vendor Bill.
     * This defines a **BelongsTo** relationship, enforcing the multi-company architecture
     * where each vendor bill belongs to a specific company .
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the Vendor (Partner) associated with the Vendor Bill.
     * Establishes a **BelongsTo** relationship with the Partner model,
     * identifying the supplier of the goods or services .
     *
     * @return BelongsTo
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'vendor_id');
    }

    /**
     * Get the Currency of the Vendor Bill.
     * Defines a **BelongsTo** relationship to the Currency model,
     * indicating the currency in which the bill is denominated .
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the Journal Entry associated with the Vendor Bill once it's posted.
     * This **BelongsTo** relationship is fundamental for the double-entry accounting system,
     * linking the business document to its corresponding immutable financial transaction .
     * The `journal_entry_id` is nullable as it is only populated upon posting .
     *
     * @return BelongsTo
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    /**
     * Get the Payments that are applied to this Vendor Bill.
     *
     * @return BelongsToMany
     */
    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class, 'payment_document_links', 'vendor_bill_id', 'payment_id')
            ->withPivot('amount_applied');
    }

    /**
     * Get the Vendor Bill Lines for the Vendor Bill.
     * Defines a **HasMany** relationship, indicating that a vendor bill can have
     * multiple line items detailing the products or services purchased .
     *
     * @return HasMany
     */
    public function lines(): HasMany
    {
        return $this->hasMany(VendorBillLine::class, 'vendor_bill_id');
    }

    /**
     * Scope a query to only include vendor bills that have been 'Posted'.
     * This facilitates querying for financial records that have had an accounting impact .
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePosted($query)
    {
        return $query->where('status', 'Posted');
    }

    /**
     * Determine if the vendor bill has been confirmed and posted to the ledger.
     * Posted bills are considered **immutable** records [4, 7].
     *
     * @return bool
     */
    public function isPosted(): bool
    {
        return $this->status === 'Posted';
    }

    /**
     * Determine if the vendor bill is currently in 'Draft' status.
     * Only bills in 'Draft' status can be directly modified or deleted .
     *
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->status === 'Draft';
    }

    /**
     * Determine if the vendor bill is considered modifiable.
     * In adherence to accounting principles, **only draft documents are modifiable**;
     * posted documents require formal contra-entries for any adjustments [1-4].
     *
     * @return bool
     */
    public function canBeModified(): bool
    {
        return $this->isDraft();
    }
}
