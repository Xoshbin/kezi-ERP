<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $original_invoice_id
 * @property int|null $original_vendor_bill_id
 * @property int|null $journal_entry_id
 * @property string $type
 * @property \Illuminate\Support\Carbon $date
 * @property string $reference_number
 * @property float $total_amount
 * @property float $total_tax
 * @property string $reason
 * @property string $status
 * @property string|null $posted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\JournalEntry|null $journalEntry
 * @property-read \App\Models\Invoice|null $originalInvoice
 * @property-read \App\Models\VendorBill|null $originalVendorBill
 * @method static \Database\Factories\AdjustmentDocumentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument whereOriginalInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument whereOriginalVendorBillId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument wherePostedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument whereReferenceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument whereTotalTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdjustmentDocument whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AdjustmentDocument extends Model
{
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
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',             // [5]
        'original_invoice_id',    // [5]
        'original_vendor_bill_id', // [5]
        'type',                   // [5] e.g., 'Credit Note', 'Debit Note', 'Miscellaneous Adjustment'
        'date',                   // [5]
        'reference_number',       // [5]
        'total_amount',           // [5]
        'total_tax',              // [5]
        'reason',                 // [5]
        'status',                 // [5] e.g., 'Draft', 'Posted'
        'journal_entry_id',       // [5]
        'posted_at'
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
        'date'         => 'date',       // [5, 6]
        'total_amount' => MoneyCast::class,  // [5] Example precision, adjust as needed.
        'total_tax'    => MoneyCast::class,  // [5] Example precision, adjust as needed.
        'created_at'   => 'datetime',   // [5, 6]
        'updated_at'   => 'datetime',   // [5, 6]
    ];

    public const STATUS_DRAFT = 'draft'; // [5]
    public const STATUS_POSTED = 'posted'; // [5]

    // use it in Filament select options columns
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_POSTED => 'Posted',
        ];
    }

    /**
     * The "booted" method of the model.
     * This is an appropriate place to enforce global constraints or event listeners.
     * In accounting, immutability of 'Posted' documents is paramount.
     * While not directly altering the model's properties, this serves as a reminder
     * that application logic MUST prevent modification of 'Posted' documents.
     *
     * @return void
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
            if ($adjustmentDocument->getOriginal('status') === 'Posted' && $adjustmentDocument->isDirty() && !$adjustmentDocument->isDirty('status')) {
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
     * Get the company that owns the adjustment document.
     * An adjustment document always belongs to a specific company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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
}
