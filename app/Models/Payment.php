<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Observers\PaymentObserver;
use App\Observers\AuditLogObserver;
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
 * @property \Illuminate\Support\Carbon $payment_date
 * @property float $amount
 * @property string $payment_type
 * @property string|null $reference
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Currency $currency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \App\Models\Journal $journal
 * @property-read \App\Models\JournalEntry|null $journalEntry
 * @property-read \App\Models\Partner $partner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PaymentDocumentLink> $paymentDocumentLinks
 * @property-read int|null $payment_document_links_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VendorBill> $vendorBills
 * @property-read int|null $vendor_bills_count
 * @method static \Database\Factories\PaymentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereJournalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaidToFromPartnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUpdatedAt($value)
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

    public const TYPE_INBOUND = 'inbound';
    public const TYPE_OUTBOUND = 'outbound';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_RECONCILED = 'reconciled';
    public const STATUS_CANCELED = 'canceled';

    /**
     * Get available payment statuses.
     *
     * @return array<string, string>
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_RECONCILED => 'Reconciled',
            self::STATUS_CANCELED => 'Canceled',
        ];
    }

    // use it in Filament select options columns
    public static function getTypes(): array
    {
        return [
            self::TYPE_INBOUND => 'Inbound',
            self::TYPE_OUTBOUND => 'Outbound',
        ];
    }

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

    /**
     * Get the BankStatementLines that are linked to this payment.
     * This relationship is established when a payment is reconciled with bank statement lines.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bankStatementLines()
    {
        return $this->hasMany(BankStatementLine::class);
    }
}
