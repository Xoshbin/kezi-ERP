<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * @property int $id
 * @property int $payment_id
 * @property int|null $invoice_id
 * @property int|null $vendor_bill_id
 * @property float $amount_applied
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Invoice|null $invoice
 * @property-read \App\Models\Payment $payment
 * @property-read \App\Models\VendorBill|null $vendorBill
 * @method static \Database\Factories\PaymentDocumentLinkFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDocumentLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDocumentLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDocumentLink query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDocumentLink whereAmountApplied($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDocumentLink whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDocumentLink whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDocumentLink whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDocumentLink wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDocumentLink whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDocumentLink whereVendorBillId($value)
 * @mixin \Eloquent
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
     * Get the currency for this payment document link through the payment.
     * This is needed for the MoneyCast to work properly.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function currency()
    {
        return $this->hasOneThrough(
            Currency::class,
            Payment::class,
            'id', // Foreign key on payments table
            'id', // Foreign key on currencies table
            'payment_id', // Local key on payment_document_links table
            'currency_id' // Local key on payments table
        );
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
