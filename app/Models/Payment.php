<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Casts\DocumentCurrencyMoneyCast;
use App\Casts\BaseCurrencyMoneyCast;
use App\Observers\PaymentObserver;
use App\Observers\AuditLogObserver;
use App\Enums\Payments\PaymentType;
use App\Enums\Payments\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

// Note: SoftDeletes trait is intentionally excluded.
// Financial transaction records like Payments, once confirmed, are immutable
// and should not be soft-deleted to maintain data integrity and audit trails [1-3].
/**
 * Class Payment
 *
 * @package App\Models
 * @property int $id
 * @property int $company_id
 * @property int $journal_id
 * @property int $currency_id
 * @property int $paid_to_from_partner_id
 * @property int|null $journal_entry_id
 * @property Carbon $payment_date
 * @property float $amount
 * @property string $payment_type
 * @property string|null $reference
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Currency $currency
 * @property-read Collection<int, Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read Journal $journal
 * @property-read JournalEntry|null $journalEntry
 * @property-read Partner $partner
 * @property-read Collection<int, PaymentDocumentLink> $paymentDocumentLinks
 * @property-read int|null $payment_document_links_count
 * @property-read Collection<int, VendorBill> $vendorBills
 * @property-read int|null $vendor_bills_count
 * @method static PaymentFactory factory($count = null, $state = [])
 * @method static Builder<static>|Payment newModelQuery()
 * @method static Builder<static>|Payment newQuery()
 * @method static Builder<static>|Payment query()
 * @method static Builder<static>|Payment whereAmount($value)
 * @method static Builder<static>|Payment whereCompanyId($value)
 * @method static Builder<static>|Payment whereCreatedAt($value)
 * @method static Builder<static>|Payment whereCurrencyId($value)
 * @method static Builder<static>|Payment whereId($value)
 * @method static Builder<static>|Payment whereJournalEntryId($value)
 * @method static Builder<static>|Payment whereJournalId($value)
 * @method static Builder<static>|Payment wherePaidToFromPartnerId($value)
 * @method static Builder<static>|Payment wherePaymentDate($value)
 * @method static Builder<static>|Payment wherePaymentType($value)
 * @method static Builder<static>|Payment whereReference($value)
 * @method static Builder<static>|Payment whereStatus($value)
 * @method static Builder<static>|Payment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[ObservedBy([AuditLogObserver::class, PaymentObserver::class])]
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
        'amount_company_currency',
        'currency_id',
        'exchange_rate_at_payment',
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
        'amount' => DocumentCurrencyMoneyCast::class, // Payment amount in payment currency
        'amount_company_currency' => BaseCurrencyMoneyCast::class, // Payment amount in company base currency
        'exchange_rate_at_payment' => 'decimal:10',
        'payment_type' => PaymentType::class,
        'status' => PaymentStatus::class,
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
        'status' => 'draft',
    ];



    /**
     * Get the Company that owns the Payment.
     * A payment is always associated with a specific company in a multi-company setup [3].
     *
     * @return BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the Journal where this payment was recorded.
     * Payments are recorded in 'Bank' or 'Cash' type journals [3].
     *
     * @return BelongsTo
     */
    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * Get the Currency of the Payment.
     * Every payment has a defined currency [3].
     *
     * @return BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the Partner (customer or vendor) associated with this Payment.
     * This relationship uses the custom foreign key 'paid_to_from_partner_id' [3].
     *
     * @return BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class, 'paid_to_from_partner_id');
    }

    /**
     * Get the JournalEntry that is generated when the Payment is confirmed.
     * This link becomes active once the payment impacts the general ledger [1, 3].
     *
     * @return BelongsTo
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
     * @return BelongsToMany
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
     * @return BelongsToMany
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
     * @return HasMany
     */
    public function paymentDocumentLinks()
    {
        return $this->hasMany(PaymentDocumentLink::class);
    }

    /**
     * Get the BankStatementLines that are linked to this payment.
     * This relationship is established when a payment is reconciled with bank statement lines.
     *
     * @return HasMany
     */
    public function bankStatementLines()
    {
        return $this->hasMany(BankStatementLine::class);
    }

    /**
     * Get all JournalEntries related to this payment.
     * This includes both the direct journal entry and any polymorphic entries (e.g., reconciliation entries).
     * Note: This is used by the JournalEntriesRelationManager which handles the complex query logic.
     *
     * @return HasMany
     */
    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class, 'source_id')
            ->where('source_type', self::class);
    }
}
