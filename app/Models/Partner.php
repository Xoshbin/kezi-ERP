<?php

namespace App\Models;

use App\Enums\Partners\PartnerType;
use App\Enums\Purchases\VendorBillStatus;
use App\Enums\Sales\InvoiceStatus;
use App\Observers\PartnerObserver;
use App\Traits\TranslatableSearch;
use Brick\Money\Money;
use Database\Factories\PartnerFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class Partner
 *
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property PartnerType $type
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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Company|null $company
 * @property-read Collection<int, Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read Collection<int, JournalEntryLine> $journalEntryLines
 * @property-read int|null $journal_entry_lines_count
 * @property-read Collection<int, Payment> $payments
 * @property-read int|null $payments_count
 * @property-read Collection<int, VendorBill> $vendorBills
 * @property-read int|null $vendor_bills_count
 *
 * @method static PartnerFactory factory($count = null, $state = [])
 * @method static Builder<static>|Partner newModelQuery()
 * @method static Builder<static>|Partner newQuery()
 * @method static Builder<static>|Partner onlyTrashed()
 * @method static Builder<static>|Partner query()
 * @method static Builder<static>|Partner whereAddressLine1($value)
 * @method static Builder<static>|Partner whereAddressLine2($value)
 * @method static Builder<static>|Partner whereCity($value)
 * @method static Builder<static>|Partner whereCompanyId($value)
 * @method static Builder<static>|Partner whereContactPerson($value)
 * @method static Builder<static>|Partner whereCountry($value)
 * @method static Builder<static>|Partner whereCreatedAt($value)
 * @method static Builder<static>|Partner whereDeletedAt($value)
 * @method static Builder<static>|Partner whereEmail($value)
 * @method static Builder<static>|Partner whereId($value)
 * @method static Builder<static>|Partner whereIsActive($value)
 * @method static Builder<static>|Partner whereName($value)
 * @method static Builder<static>|Partner wherePhone($value)
 * @method static Builder<static>|Partner whereState($value)
 * @method static Builder<static>|Partner whereTaxId($value)
 * @method static Builder<static>|Partner whereType($value)
 * @method static Builder<static>|Partner whereUpdatedAt($value)
 * @method static Builder<static>|Partner whereZipCode($value)
 * @method static Builder<static>|Partner withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Partner withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[ObservedBy([PartnerObserver::class])]
class Partner extends Model
{
    use HasFactory, SoftDeletes;
    use TranslatableSearch;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
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
     * Get the non-translatable fields that should be searched.
     *
     * @return array<int, string>
     */
    public function getNonTranslatableSearchFields(): array
    {
        return ['name', 'email', 'contact_person'];
    }

    /**
     * Get the company that owns the Partner.
     *
     * @return BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the receivable account for this partner.
     *
     * @return BelongsTo
     */
    public function receivableAccount()
    {
        return $this->belongsTo(Account::class, 'receivable_account_id');
    }

    /**
     * Get the payable account for this partner.
     *
     * @return BelongsTo
     */
    public function payableAccount()
    {
        return $this->belongsTo(Account::class, 'payable_account_id');
    }

    /**
     * Get the invoices for the Partner (as a customer).
     *
     * @return HasMany
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    /**
     * Get the vendor bills for the Partner (as a vendor).
     *
     * @return HasMany
     */
    public function vendorBills()
    {
        return $this->hasMany(VendorBill::class, 'vendor_id');
    }

    /**
     * Get the payments associated with the Partner.
     *
     * @return HasMany
     */
    public function payments()
    {
        // payments.paid_to_from_partner_id is FK to partners.id [5]
        return $this->hasMany(Payment::class, 'paid_to_from_partner_id');
    }

    /**
     * Get the journal entry lines for the Partner.
     *
     * @return HasMany
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
     */
    public function getCustomerOutstandingBalance(): Money
    {
        if (! in_array($this->type, [PartnerType::Customer, PartnerType::Both])) {
            $this->loadMissing('company.currency');

            if (!$this->company?->currency) {
                throw new \RuntimeException('Partner company or currency not found');
            }

            return Money::of(0, $this->company->currency->code);
        }

        $this->loadMissing('company.currency');

        if (!$this->company?->currency) {
            throw new \RuntimeException('Partner company or currency not found');
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Invoice> $invoices */
        $invoices = $this->invoices()
            ->whereIn('status', [InvoiceStatus::Posted, InvoiceStatus::Paid])
            ->get();

        $totalOutstanding = $invoices->sum(function (Invoice $invoice) {
            return $invoice->getRemainingAmount()->getMinorAmount()->toInt();
        });

        return Money::ofMinor($totalOutstanding, $this->company->currency->code);
    }

    /**
     * Get the total outstanding vendor balance (accounts payable).
     * This represents money that we owe to vendors from unpaid bills.
     */
    public function getVendorOutstandingBalance(): Money
    {
        if (! in_array($this->type, [PartnerType::Vendor, PartnerType::Both])) {
            $this->loadMissing('company.currency');

            if (!$this->company?->currency) {
                throw new \RuntimeException('Partner company or currency not found');
            }

            return Money::of(0, $this->company->currency->code);
        }

        $this->loadMissing('company.currency');

        if (!$this->company?->currency) {
            throw new \RuntimeException('Partner company or currency not found');
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, VendorBill> $vendorBills */
        $vendorBills = $this->vendorBills()
            ->whereIn('status', [VendorBillStatus::Posted, VendorBillStatus::Paid])
            ->get();

        $totalOutstanding = $vendorBills->sum(function (VendorBill $bill) {
            return $bill->getRemainingAmount()->getMinorAmount()->toInt();
        });

        return Money::ofMinor($totalOutstanding, $this->company->currency->code);
    }

    /**
     * Get the overdue customer balance (past due invoices).
     * This represents money from invoices that are past their due date.
     */
    public function getCustomerOverdueBalance(): Money
    {
        if (! in_array($this->type, [PartnerType::Customer, PartnerType::Both])) {
            $this->loadMissing('company.currency');

            if (!$this->company?->currency) {
                throw new \RuntimeException('Partner company or currency not found');
            }

            return Money::of(0, $this->company->currency->code);
        }

        $this->loadMissing('company.currency');

        if (!$this->company?->currency) {
            throw new \RuntimeException('Partner company or currency not found');
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Invoice> $overdueInvoices */
        $overdueInvoices = $this->invoices()
            ->whereIn('status', [InvoiceStatus::Posted, InvoiceStatus::Paid])
            ->where('due_date', '<', Carbon::today())
            ->get();

        $totalOverdue = $overdueInvoices->sum(function (Invoice $invoice) {
            return $invoice->getRemainingAmount()->getMinorAmount()->toInt();
        });

        return Money::ofMinor($totalOverdue, $this->company->currency->code);
    }

    /**
     * Get the overdue vendor balance (past due bills).
     * This represents money from bills that are past their due date.
     */
    public function getVendorOverdueBalance(): Money
    {
        if (! in_array($this->type, [PartnerType::Vendor, PartnerType::Both])) {
            $this->loadMissing('company.currency');

            if (!$this->company?->currency) {
                throw new \RuntimeException('Partner company or currency not found');
            }

            return Money::of(0, $this->company->currency->code);
        }

        $this->loadMissing('company.currency');

        if (!$this->company?->currency) {
            throw new \RuntimeException('Partner company or currency not found');
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, VendorBill> $overdueBills */
        $overdueBills = $this->vendorBills()
            ->whereIn('status', [VendorBillStatus::Posted, VendorBillStatus::Paid])
            ->where('due_date', '<', Carbon::today())
            ->get();

        $totalOverdue = $overdueBills->sum(function (VendorBill $bill) {
            return $bill->getRemainingAmount()->getMinorAmount()->toInt();
        });

        return Money::ofMinor($totalOverdue, $this->company->currency->code);
    }

    /**
     * Get the last transaction date for this partner.
     * Returns the most recent date from invoices, vendor bills, or payments.
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
     */
    public function hasOverdueAmounts(): bool
    {
        return ! $this->getCustomerOverdueBalance()->isZero() ||
               ! $this->getVendorOverdueBalance()->isZero();
    }

    /**
     * Get the total transaction volume (lifetime value) for this partner.
     * This includes all posted invoices and vendor bills regardless of payment status.
     */
    public function getTotalLifetimeValue(): Money
    {
        $this->loadMissing('company.currency');

        if (!$this->company?->currency) {
            throw new \RuntimeException('Partner company or currency not found');
        }

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
        if (! in_array($this->type, [PartnerType::Customer, PartnerType::Both])) {
            $this->loadMissing('company.currency');

            if (!$this->company?->currency) {
                throw new \RuntimeException('Partner company or currency not found');
            }

            return Money::of(0, $this->company->currency->code);
        }

        $this->loadMissing('company.currency');

        if (!$this->company?->currency) {
            throw new \RuntimeException('Partner company or currency not found');
        }

        $dueDate = Carbon::today()->addDays($days);

        /** @var \Illuminate\Database\Eloquent\Collection<int, Invoice> $dueInvoices */
        $dueInvoices = $this->invoices()
            ->whereIn('status', [InvoiceStatus::Posted, InvoiceStatus::Paid])
            ->where('due_date', '<=', $dueDate)
            ->where('due_date', '>=', Carbon::today())
            ->get();

        $totalDue = $dueInvoices->sum(function (Invoice $invoice) {
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
        if (! in_array($this->type, [PartnerType::Vendor, PartnerType::Both])) {
            $this->loadMissing('company.currency');

            if (!$this->company?->currency) {
                throw new \RuntimeException('Partner company or currency not found');
            }

            return Money::of(0, $this->company->currency->code);
        }

        $this->loadMissing('company.currency');

        if (!$this->company?->currency) {
            throw new \RuntimeException('Partner company or currency not found');
        }

        $dueDate = Carbon::today()->addDays($days);

        /** @var \Illuminate\Database\Eloquent\Collection<int, VendorBill> $dueBills */
        $dueBills = $this->vendorBills()
            ->whereIn('status', [VendorBillStatus::Posted, VendorBillStatus::Paid])
            ->where('due_date', '<=', $dueDate)
            ->where('due_date', '>=', Carbon::today())
            ->get();

        $totalDue = $dueBills->sum(function (VendorBill $bill) {
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

        if (!$this->company?->currency) {
            throw new \RuntimeException('Partner company or currency not found');
        }

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
