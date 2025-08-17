<?php

namespace App\Models;

use Carbon\Carbon;
use App\Observers\CompanyObserver;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class Company
 *
 * @package App\Models
 *
 * This Eloquent model represents a distinct legal entity or branch within the
 * multi-company accounting system. It serves as the root for all financial
 * data and configurations, ensuring proper segregation and adherence to
 * specific fiscal requirements.
 * @property int $id
 * @property string $name
 * @property string|null $address
 * @property string|null $tax_id
 * @property int $currency_id
 * @property string $fiscal_country
 * @property int|null $parent_company_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection<int, Account> $accounts
 * @property-read int|null $accounts_count
 * @property-read Collection<int, AdjustmentDocument> $adjustmentDocuments
 * @property-read int|null $adjustment_documents_count
 * @property-read Collection<int, AnalyticAccount> $analyticAccounts
 * @property-read int|null $analytic_accounts_count
 * @property-read Collection<int, AnalyticPlan> $analyticPlans
 * @property-read int|null $analytic_plans_count
 * @property-read Collection<int, Asset> $assets
 * @property-read int|null $assets_count
 * @property-read Collection<int, AuditLog> $auditLogs
 * @property-read int|null $audit_logs_count
 * @property-read Collection<int, Budget> $budgets
 * @property-read int|null $budgets_count
 * @property-read Collection<int, Company> $childrenCompanies
 * @property-read int|null $children_companies_count
 * @property-read Currency $currency
 * @property-read Collection<int, FiscalPosition> $fiscalPositions
 * @property-read int|null $fiscal_positions_count
 * @property-read Collection<int, Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read Collection<int, JournalEntry> $journalEntries
 * @property-read int|null $journal_entries_count
 * @property-read Collection<int, Journal> $journals
 * @property-read int|null $journals_count
 * @property-read Collection<int, LockDate> $lockDates
 * @property-read int|null $lock_dates_count
 * @property-read Company|null $parentCompany
 * @property-read Collection<int, Partner> $partners
 * @property-read int|null $partners_count
 * @property-read Collection<int, Payment> $payments
 * @property-read int|null $payments_count
 * @property-read Collection<int, Product> $products
 * @property-read int|null $products_count
 * @property-read Collection<int, Tax> $taxes
 * @property-read int|null $taxes_count
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 * @property-read Collection<int, VendorBill> $vendorBills
 * @property-read int|null $vendor_bills_count
 * @method static CompanyFactory factory($count = null, $state = [])
 * @method static Builder<static>|Company newModelQuery()
 * @method static Builder<static>|Company newQuery()
 * @method static Builder<static>|Company query()
 * @method static Builder<static>|Company whereAddress($value)
 * @method static Builder<static>|Company whereCreatedAt($value)
 * @method static Builder<static>|Company whereCurrencyId($value)
 * @method static Builder<static>|Company whereFiscalCountry($value)
 * @method static Builder<static>|Company whereId($value)
 * @method static Builder<static>|Company whereName($value)
 * @method static Builder<static>|Company whereParentCompanyId($value)
 * @method static Builder<static>|Company whereTaxId($value)
 * @method static Builder<static>|Company whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[ObservedBy([CompanyObserver::class])]
class Company extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * Carefully defining fillable attributes is paramount in a financial system
     * to prevent unauthorized mass assignment vulnerabilities and maintain
     * data integrity [1].
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'tax_id',
        'currency_id',
        'fiscal_country',
        'parent_company_id',
        'default_accounts_payable_id',
        'default_tax_receivable_id',
        'default_purchase_journal_id',
        'default_accounts_receivable_id',
        'default_sales_discount_account_id',
        'default_tax_account_id',
        'default_sales_journal_id',
        'default_depreciation_journal_id',
        'default_bank_account_id',
        'default_outstanding_receipts_account_id',
        'default_bank_journal_id',
        'default_gain_loss_account_id',
        'inventory_adjustment_account_id',
        'default_stock_location_id',
        'default_vendor_location_id',
        // PDF Settings
        'pdf_template',
        'pdf_logo_path',
        'pdf_settings',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // No special casts explicitly mentioned in the schema beyond default timestamps.
        // 'created_at' and 'updated_at' are Carbon instances by default.
        'pdf_settings' => 'json',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    |
    | Eloquent's relationship methods define how models interact with each other.
    | These are critical for navigating the interconnected financial data,
    | ensuring that all records are properly attributed to their owning company.
    |
    */
    /**
     * Get the default operating currency for the company.
     * A company operates within a specific default currency for its financial records [1, 4].
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the parent company if this company is a branch or subsidiary.
     * Supports multi-branch/multi-company structures [1, 3].
     *
     * @return BelongsTo
     */
    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    /**
     * Get the child companies if this company is a parent.
     * This defines the hierarchical structure within the business group [1, 3].
     *
     * @return HasMany
     */
    public function childrenCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }

    /**
     * Get the users associated with this company.
     * In a multi-company setup, users typically belong to a specific company [1].
     *
     * @return BelongToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')->withTimestamps();
    }

    /**
     * Get the audit logs associated with actions performed within this company.
     * Comprehensive auditability is a non-negotiable principle for accounting software [1].
     *
     * @return HasMany
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get the lock dates configured for this company.
     * Lock dates are crucial for preventing modifications to historical financial periods [1].
     *
     * @return HasMany
     */
    public function lockDates(): HasMany
    {
        return $this->hasMany(LockDate::class);
    }

    /**
     * Get the chart of accounts (accounts) belonging to this company.
     * Each company maintains its own unique chart of accounts [1, 5].
     *
     * @return HasMany
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Get the journals belonging to this company.
     * Journals categorize and sequence financial transactions [1, 6-8].
     *
     * @return HasMany
     */
    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    /**
     * Get the journal entries posted by this company.
     * Journal entries are the immutable records of all financial transactions [1].
     *
     * @return HasMany
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * Get the customer invoices issued by this company.
     *
     * @return HasMany
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the vendor bills received by this company.
     *
     * @return HasMany
     */
    public function vendorBills(): HasMany
    {
        return $this->hasMany(VendorBill::class);
    }

    /**
     * Get the payments (inbound/outbound) processed by this company.
     *
     * @return HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the adjustment documents (e.g., credit/debit notes) created by this company.
     *
     * @return HasMany
     */
    public function adjustmentDocuments(): HasMany
    {
        return $this->hasMany(AdjustmentDocument::class);
    }

    /**
     * Get the partners (customers/vendors) associated with this company.
     * Partners can be defined per internal company [2].
     *
     * @return HasMany
     */
    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class);
    }

    /**
     * Get the products managed by this company.
     * Products can be company-specific [2].
     *
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the tax definitions for this company.
     * Taxes are configured per company [2].
     *
     * @return HasMany
     */
    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    /**
     * Get the fiscal positions defined for this company.
     * Fiscal positions handle tax and account mapping based on partner location/type [2].
     *
     * @return HasMany
     */
    public function fiscalPositions(): HasMany
    {
        return $this->hasMany(FiscalPosition::class);
    }

    /**
     * Get the fixed assets owned by this company.
     *
     * @return HasMany
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Get the analytic accounts defined for this company.
     * Used for management/cost accounting, separate from general ledger accounts [2, 9].
     *
     * @return HasMany
     */
    public function analyticAccounts(): HasMany
    {
        return $this->hasMany(AnalyticAccount::class);
    }

    /**
     * Get the analytic plans defined for this company.
     * Used to group analytic accounts or define budget structures [2].
     *
     * @return HasMany
     */
    public function analyticPlans(): HasMany
    {
        return $this->hasMany(AnalyticPlan::class);
    }

    /**
     * Get the budgets created for this company.
     *
     * @return HasMany
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function defaultAccountsPayable(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_accounts_payable_id');
    }

    public function defaultTaxReceivable(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_tax_receivable_id');
    }

    public function defaultPurchaseJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'default_purchase_journal_id');
    }

    public function defaultAccountsReceivable(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_accounts_receivable_id');
    }

    public function defaultSalesDiscountAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_sales_discount_account_id');
    }

    public function defaultTaxAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_tax_account_id');
    }

    public function defaultSalesJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'default_sales_journal_id');
    }

    public function defaultDepreciationJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'default_depreciation_journal_id');
    }

    public function defaultBankJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'default_bank_journal_id');
    }

    public function defaultBankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_bank_account_id');
    }

    public function defaultOutstandingReceiptsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_outstanding_receipts_account_id');
    }

    /**
     * Get the default account for recording gains or losses on asset disposal.
     */
    public function defaultGainLossAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_gain_loss_account_id');
    }

    public function inventoryAdjustmentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'inventory_adjustment_account_id');
    }

    /**
     * Check if a given date is within a locked period for the company.
     *
     * This method is essential for enforcing the immutability of posted transactions,
     * a core principle of the accounting system [1, 8]. It queries the `lock_dates`
     * table to determine if any lock date prevents transactions on the given date.
     *
     * @param string|Carbon $date The date to check.
     * @return bool True if the date is locked, false otherwise.
     */
    public function isDateLocked($date): bool
    {
        $dateToCheck = Carbon::parse($date)->startOfDay();

        return $this->lockDates()
            ->where('locked_until', '>=', $dateToCheck)
            ->exists();
    }

    /**
     * The company's default stock location for internal operations.
     */
    public function defaultStockLocation(): BelongsTo
    {
        // CORRECTED: This is a BelongsTo relationship because the
        // 'default_stock_location_id' foreign key is on the 'companies' table.
        return $this->belongsTo(StockLocation::class, 'default_stock_location_id');
    }

    /**
     * The company's default location representing external vendors.
     */
    public function vendorLocation(): BelongsTo
    {
        // CORRECTED: The foreign key in the database is 'default_vendor_location_id',
        // not 'vendor_location_id'. We align the model with the schema.
        return $this->belongsTo(StockLocation::class, 'default_vendor_location_id');
    }
}
