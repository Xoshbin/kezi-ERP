<?php

namespace App\Models;

use App\Observers\PartnerObserver;
use App\Enums\Partners\PartnerType;
use App\Enums\Sales\InvoiceStatus;
use App\Enums\Purchases\VendorBillStatus;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Support\Carbon;

/**
 * Class Partner
 *
 * @package App\Models
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string $type
 * @property string|null $contact_person
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address_line_1
 * @property string|null $address_line_2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip_code
 * @property string|null $country
 * @property string|null $tax_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Company|null $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JournalEntryLine> $journalEntryLines
 * @property-read int|null $journal_entry_lines_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VendorBill> $vendorBills
 * @property-read int|null $vendor_bills_count
 * @method static \Database\Factories\PartnerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereAddressLine1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereAddressLine2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereContactPerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner whereZipCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partner withoutTrashed()
 * @mixin \Eloquent
 */

#[ObservedBy([PartnerObserver::class])]
class Partner extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'type',
        'contact_person',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip_code',
        'country',
        'tax_id',
        'receivable_account_id',
        'payable_account_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => PartnerType::class,
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];



    /**
     * Get the company that owns the Partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the receivable account for this partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function receivableAccount()
    {
        return $this->belongsTo(Account::class, 'receivable_account_id');
    }

    /**
     * Get the payable account for this partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payableAccount()
    {
        return $this->belongsTo(Account::class, 'payable_account_id');
    }

    /**
     * Get the invoices for the Partner (as a customer).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    /**
     * Get the vendor bills for the Partner (as a vendor).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vendorBills()
    {
        return $this->hasMany(VendorBill::class, 'vendor_id');
    }

    /**
     * Get the payments associated with the Partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments()
    {
        // payments.paid_to_from_partner_id is FK to partners.id [5]
        return $this->hasMany(Payment::class, 'paid_to_from_partner_id');
    }

    /**
     * Get the journal entry lines for the Partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function journalEntryLines()
    {
        // journal_entry_lines.partner_id is Nullable FK to partners.id [6]
        return $this->hasMany(JournalEntryLine::class, 'partner_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Financial Balance Calculations
    |--------------------------------------------------------------------------
    */

    /**
     * Get the total outstanding customer balance (accounts receivable).
     * This represents money that customers owe us from unpaid invoices.
     *
     * @return Money
     */
    public function getCustomerOutstandingBalance(): Money
    {
        if (!in_array($this->type, [PartnerType::Customer, PartnerType::Both])) {
            $this->loadMissing('company.currency');
            return Money::of(0, $this->company->currency->code);
        }

        $this->loadMissing('company.currency');

        $totalOutstanding = $this->invoices()
            ->whereIn('status', [InvoiceStatus::Posted, InvoiceStatus::Paid])
            ->get()
            ->sum(function ($invoice) {
                return $invoice->getRemainingAmount()->getMinorAmount()->toInt();
            });

        return Money::ofMinor($totalOutstanding, $this->company->currency->code);
    }

    /**
     * Get the total outstanding vendor balance (accounts payable).
     * This represents money that we owe to vendors from unpaid bills.
     *
     * @return Money
     */
    public function getVendorOutstandingBalance(): Money
    {
        if (!in_array($this->type, [PartnerType::Vendor, PartnerType::Both])) {
            $this->loadMissing('company.currency');
            return Money::of(0, $this->company->currency->code);
        }

        $this->loadMissing('company.currency');

        $totalOutstanding = $this->vendorBills()
            ->whereIn('status', [VendorBillStatus::Posted, VendorBillStatus::Paid])
            ->get()
            ->sum(function ($bill) {
                return $bill->getRemainingAmount()->getMinorAmount()->toInt();
            });

        return Money::ofMinor($totalOutstanding, $this->company->currency->code);
    }

    /**
     * Get the overdue customer balance (past due invoices).
     * This represents money from invoices that are past their due date.
     *
     * @return Money
     */
    public function getCustomerOverdueBalance(): Money
    {
        if (!in_array($this->type, [PartnerType::Customer, PartnerType::Both])) {
            $this->loadMissing('company.currency');
            return Money::of(0, $this->company->currency->code);
        }

        $this->loadMissing('company.currency');

        $totalOverdue = $this->invoices()
            ->whereIn('status', [InvoiceStatus::Posted, InvoiceStatus::Paid])
            ->where('due_date', '<', Carbon::today())
            ->get()
            ->sum(function ($invoice) {
                return $invoice->getRemainingAmount()->getMinorAmount()->toInt();
            });

        return Money::ofMinor($totalOverdue, $this->company->currency->code);
    }

    /**
     * Get the overdue vendor balance (past due bills).
     * This represents money from bills that are past their due date.
     *
     * @return Money
     */
    public function getVendorOverdueBalance(): Money
    {
        if (!in_array($this->type, [PartnerType::Vendor, PartnerType::Both])) {
            $this->loadMissing('company.currency');
            return Money::of(0, $this->company->currency->code);
        }

        $this->loadMissing('company.currency');

        $totalOverdue = $this->vendorBills()
            ->whereIn('status', [VendorBillStatus::Posted, VendorBillStatus::Paid])
            ->where('due_date', '<', Carbon::today())
            ->get()
            ->sum(function ($bill) {
                return $bill->getRemainingAmount()->getMinorAmount()->toInt();
            });

        return Money::ofMinor($totalOverdue, $this->company->currency->code);
    }

    /**
     * Get the last transaction date for this partner.
     * Returns the most recent date from invoices, vendor bills, or payments.
     *
     * @return Carbon|null
     */
    public function getLastTransactionDate(): ?Carbon
    {
        $lastInvoiceDate = $this->invoices()->max('invoice_date');
        $lastBillDate = $this->vendorBills()->max('bill_date');
        $lastPaymentDate = $this->payments()->max('payment_date');

        $dates = array_filter([
            $lastInvoiceDate ? Carbon::parse($lastInvoiceDate) : null,
            $lastBillDate ? Carbon::parse($lastBillDate) : null,
            $lastPaymentDate ? Carbon::parse($lastPaymentDate) : null,
        ]);

        return empty($dates) ? null : max($dates);
    }

    /**
     * Check if this partner has any overdue amounts.
     *
     * @return bool
     */
    public function hasOverdueAmounts(): bool
    {
        return !$this->getCustomerOverdueBalance()->isZero() ||
               !$this->getVendorOverdueBalance()->isZero();
    }

    /**
     * Get the total transaction volume (lifetime value) for this partner.
     * This includes all posted invoices and vendor bills regardless of payment status.
     *
     * @return Money
     */
    public function getTotalLifetimeValue(): Money
    {
        $this->loadMissing('company.currency');

        $invoiceTotal = $this->invoices()
            ->whereIn('status', [InvoiceStatus::Posted, InvoiceStatus::Paid])
            ->sum('total_amount');

        $billTotal = $this->vendorBills()
            ->whereIn('status', [VendorBillStatus::Posted, VendorBillStatus::Paid])
            ->sum('total_amount');

        $total = ($invoiceTotal ?: 0) + ($billTotal ?: 0);

        return Money::ofMinor($total, $this->company->currency->code);
    }

    /*
    |--------------------------------------------------------------------------
    | Simple Widget Methods (Performance Optimized)
    |--------------------------------------------------------------------------
    */

    /**
     * Get customer amounts due within specified days.
     * Simplified for performance.
     */
    public function getCustomerDueWithinDays(int $days): Money
    {
        if (!in_array($this->type, [PartnerType::Customer, PartnerType::Both])) {
            $this->loadMissing('company.currency');
            return Money::of(0, $this->company->currency->code);
        }

        $this->loadMissing('company.currency');

        $dueDate = Carbon::today()->addDays($days);

        $totalDue = $this->invoices()
            ->whereIn('status', [InvoiceStatus::Posted, InvoiceStatus::Paid])
            ->where('due_date', '<=', $dueDate)
            ->where('due_date', '>=', Carbon::today())
            ->get()
            ->sum(function ($invoice) {
                return $invoice->getRemainingAmount()->getMinorAmount()->toInt();
            });

        return Money::ofMinor($totalDue, $this->company->currency->code);
    }

    /**
     * Get vendor amounts due within specified days.
     * Simplified for performance.
     */
    public function getVendorDueWithinDays(int $days): Money
    {
        if (!in_array($this->type, [PartnerType::Vendor, PartnerType::Both])) {
            $this->loadMissing('company.currency');
            return Money::of(0, $this->company->currency->code);
        }

        $this->loadMissing('company.currency');

        $dueDate = Carbon::today()->addDays($days);

        $totalDue = $this->vendorBills()
            ->whereIn('status', [VendorBillStatus::Posted, VendorBillStatus::Paid])
            ->where('due_date', '<=', $dueDate)
            ->where('due_date', '>=', Carbon::today())
            ->get()
            ->sum(function ($bill) {
                return $bill->getRemainingAmount()->getMinorAmount()->toInt();
            });

        return Money::ofMinor($totalDue, $this->company->currency->code);
    }

    /**
     * Get average payment days - simplified calculation.
     */
    public function getCustomerAveragePaymentDays(): int
    {
        // Simple calculation - return 0 for now to avoid complex queries
        return 0;
    }

    /**
     * Get average payment days for vendor - simplified calculation.
     */
    public function getVendorAveragePaymentDays(): int
    {
        // Simple calculation - return 0 for now to avoid complex queries
        return 0;
    }

    /**
     * Get transaction value for current month - simplified.
     */
    public function getMonthlyTransactionValue(): Money
    {
        $this->loadMissing('company.currency');

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $invoiceTotal = $this->invoices()
            ->whereIn('status', [InvoiceStatus::Posted, InvoiceStatus::Paid])
            ->whereBetween('invoice_date', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        $billTotal = $this->vendorBills()
            ->whereIn('status', [VendorBillStatus::Posted, VendorBillStatus::Paid])
            ->whereBetween('bill_date', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        $total = ($invoiceTotal ?: 0) + ($billTotal ?: 0);

        return Money::ofMinor($total, $this->company->currency->code);
    }
}
