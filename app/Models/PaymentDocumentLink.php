<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id The primary key for the record.
 * @property int $payment_id The foreign key linking to the Payment model.
 * @property int|null $invoice_id The foreign key linking to the Invoice model (nullable if linked to a vendor bill).
 * @property int|null $vendor_bill_id The foreign key linking to the VendorBill model (nullable if linked to an invoice).
 * @property float $amount_applied The specific amount of the payment applied to this document.
 * @property \Illuminate\Support\Carbon|null $created_at Timestamp when the record was created.
 * @property \Illuminate\Support\Carbon|null $updated_at Timestamp when the record was last updated.
 */
class PaymentDocumentLink extends Model
{
    use HasFactory;

    /**
     * The database table associated with the model.
     * This table serves as a pivot for linking payments to invoices or vendor bills.
     *
     * @var string
     */
    protected $table = 'payment_document_links';

    /**
     * The attributes that are mass assignable.
     * These fields can be safely filled via mass assignment operations.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payment_id',
        'invoice_id',
        'vendor_bill_id',
        'amount_applied',
    ];

    /**
     * The attributes that should be cast to native types.
     * This ensures data types are consistently managed, especially for financial amounts and dates.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount_applied' => MoneyCast::class, // Ensures the amount is treated as a decimal with 2 places for precision.  [3]
        'created_at' => 'datetime', // Automatically casts to Carbon instances for convenient date manipulation.  [4]
        'updated_at' => 'datetime', // Automatically casts to Carbon instances.  [4]
    ];

    /**
     * Get the payment that owns this document link.
     * This defines a one-to-many (inverse) relationship, where a PaymentDocumentLink belongs to a Payment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the customer invoice that this document link is associated with.
     * This is a conditional relationship, as a link will be to either an invoice or a vendor bill.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the vendor bill that this document link is associated with.
     * This is a conditional relationship, as a link will be to either a vendor bill or an invoice.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vendorBill(): BelongsTo
    {
        return $this->belongsTo(VendorBill::class);
    }

    /**
     * The "booted" method of the model.
     * This static method is invoked once when the model is first loaded. It's an opportune
     * place to register model event listeners for enforcing business rules.
     *
     * For financial records, maintaining **data integrity is paramount** [6-9].
     * We enforce the critical constraint that a `PaymentDocumentLink` **must be associated with either an invoice or a vendor bill** .
     * While robust validation should ideally occur at the incoming request or service layer [9],
     * this model-level check serves as an important **last line of defense** to prevent inconsistent data states.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (PaymentDocumentLink $link) {
            // Ensure at least one of invoice_id or vendor_bill_id is present upon creation.
            if (is_null($link->invoice_id) && is_null($link->vendor_bill_id)) {
                throw new \InvalidArgumentException('A PaymentDocumentLink must be associated with either an invoice or a vendor bill.');
            }
        });

        static::updating(function (PaymentDocumentLink $link) {
            // Ensure at least one of invoice_id or vendor_bill_id remains present upon update.
            // Direct modification of posted financial transactions is generally prevented,
            // but this safeguards against logical inconsistencies if updates were permitted in specific contexts. [6]
            if (is_null($link->invoice_id) && is_null($link->vendor_bill_id)) {
                throw new \InvalidArgumentException('A PaymentDocumentLink must be associated with either an invoice or a vendor bill.');
            }
        });
    }
}
