<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdjustmentDocument extends Model
{
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
        'total_amount' => 'decimal:4',  // [5] Example precision, adjust as needed.
        'total_tax'    => 'decimal:4',  // [5] Example precision, adjust as needed.
        'created_at'   => 'datetime',   // [5, 6]
        'updated_at'   => 'datetime',   // [5, 6]
    ];

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
