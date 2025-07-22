<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Observers\AuditLogObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// Note: SoftDeletes trait is intentionally excluded.
// Financial transaction records like Payments, once confirmed, are immutable
// and should not be soft-deleted to maintain data integrity and audit trails [1-3].

/**
 * Class Payment
 *
 * @package App\Models
 *
 * @property int $id Primary Key, auto-increment [3].
 * @property int $company_id Foreign Key to companies.id, linking the payment to a specific company [3].
 * @property int $journal_id Foreign Key to journals.id, indicating the 'Bank' or 'Cash' journal used for the payment [3].
 * @property \Illuminate\Support\Carbon $payment_date Date of the payment [3].
 * @property float $amount Decimal, the value of the payment [3].
 * @property int $currency_id Foreign Key to currencies.id, specifying the payment's currency [3].
 * @property string $payment_type Type of payment (e.g., 'Inbound' for receipts, 'Outbound' for disbursements) [3].
 * @property string|null $reference String, such as a check number or manual transaction ID [1, 3].
 * @property string $status Status of the payment (e.g., 'Draft', 'Confirmed', 'Reconciled') [1, 3].
 * @property int|null $paid_to_from_partner_id Nullable Foreign Key to partners.id, identifying the customer or vendor involved [3].
 * @property int|null $journal_entry_id Nullable Foreign Key to journal_entries.id, created upon confirmation/posting [3].
 * @property \Illuminate\Support\Carbon|null $created_at Timestamp when the record was created [3].
 * @property \Illuminate\Support\Carbon|null $updated_at Timestamp when the record was last updated [3].
 *
 * @property-read \App\Models\Company $company The company to which this payment belongs.
 * @property-read \App\Models\Journal $journal The bank or cash journal associated with this payment.
 * @property-read \App\Models\Currency $currency The currency of the payment.
 * @property-read \App\Models\Partner|null $partner The customer or vendor linked to this payment.
 * @property-read \App\Models\JournalEntry|null $journalEntry The corresponding journal entry generated for this payment.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Invoice[] $invoices The invoices this payment has been applied to.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VendorBill[] $vendorBills The vendor bills this payment has been applied to.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PaymentDocumentLink[] $paymentDocumentLinks The underlying pivot records for payment application.
 */
#[ObservedBy([AuditLogObserver::class])]
class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * These fields are explicitly allowed for mass assignment for secure data entry [9].
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'journal_id',
        'payment_date',
        'amount',
        'currency_id',
        'payment_type',
        'reference',
        'status',
        'paid_to_from_partner_id',
        'journal_entry_id',
    ];

    /**
     * The attributes that should be cast to native types.
     * This ensures data consistency and proper type handling within the application [3, 10].
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payment_date' => 'date', // Casts to a Carbon date object [3, 11].
        'amount' => MoneyCast::class, // Ensures the amount is treated as a decimal with 2 places for precision [3].
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     * New payments typically start in a 'Draft' state [1, 3].
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'Draft',
    ];

    /**
     * Get the Company that owns the Payment.
     * A payment is always associated with a specific company in a multi-company setup [3].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the Journal where this payment was recorded.
     * Payments are recorded in 'Bank' or 'Cash' type journals [3].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * Get the Currency of the Payment.
     * Every payment has a defined currency [3].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the Partner (customer or vendor) associated with this Payment.
     * This relationship uses the custom foreign key 'paid_to_from_partner_id' [3].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class, 'paid_to_from_partner_id');
    }

    /**
     * Get the JournalEntry that is generated when the Payment is confirmed.
     * This link becomes active once the payment impacts the general ledger [1, 3].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Get the Invoices that this Payment is applied to.
     * Payments can be linked to multiple invoices through a pivot table,
     * allowing for partial payments and granular application tracking [3, 12].
     * The `amount_applied` pivot field captures the specific amount allocated to each invoice [3].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'payment_document_links', 'payment_id', 'invoice_id')
            ->withPivot('amount_applied');
    }

    /**
     * Get the VendorBills that this Payment is applied to.
     * Similar to invoices, payments can cover multiple vendor bills,
     * with `amount_applied` tracking the distribution [3, 12].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function vendorBills()
    {
        return $this->belongsToMany(VendorBill::class, 'payment_document_links', 'payment_id', 'vendor_bill_id')
            ->withPivot('amount_applied');
    }

    /**
     * Get the direct PaymentDocumentLink records for this payment.
     * This provides access to the raw pivot data, enabling more complex logic if needed [3, 12].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function paymentDocumentLinks()
    {
        return $this->hasMany(PaymentDocumentLink::class);
    }
}
