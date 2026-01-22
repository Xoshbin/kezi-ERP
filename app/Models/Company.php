<?php

namespace App\Models;

use App\Observers\CompanyObserver;
use Carbon\Carbon;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\Tax;
use Modules\Inventory\Models\StockLocation;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;

/**
 * Class Company
 *
 * @property int $id
 * @property string $name
 * @property string|null $address
 * @property string|null $tax_id
 * @property int $currency_id
 * @property string $fiscal_country
 * @property int|null $parent_company_id
 * @property int|null $default_salary_payable_account_id
 * @property int|null $default_salary_expense_account_id
 * @property int|null $default_payroll_journal_id
 * @property int|null $default_income_tax_payable_account_id
 * @property int|null $default_social_security_payable_account_id
 * @property int|null $default_health_insurance_payable_account_id
 * @property int|null $default_pension_payable_account_id
 * @property int|null $default_employee_advance_receivable_account_id
 * @property \Modules\Accounting\Enums\Consolidation\ConsolidationMethod $consolidation_method
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection<int, \Modules\Accounting\Models\Account> $accounts
 * @property-read int|null $accounts_count
 * @property-read Collection<int, \Modules\Inventory\Models\AdjustmentDocument> $adjustmentDocuments
 * @property-read int|null $adjustment_documents_count
 * @property-read Collection<int, \Modules\Accounting\Models\AnalyticAccount> $analyticAccounts
 * @property-read int|null $analytic_accounts_count
 * @property-read Collection<int, \Modules\Accounting\Models\AnalyticPlan> $analyticPlans
 * @property-read int|null $analytic_plans_count
 * @property-read Collection<int, \Modules\Accounting\Models\Asset> $assets
 * @property-read int|null $assets_count
 * @property-read Collection<int, \Modules\Foundation\Models\AuditLog> $auditLogs
 * @property-read int|null $audit_logs_count
 * @property-read Collection<int, \Modules\Accounting\Models\Budget> $budgets
 * @property-read int|null $budgets_count
 * @property-read Collection<int, Company> $childrenCompanies
 * @property-read int|null $children_companies_count
 * @property-read \Modules\Foundation\Models\Currency $currency
 * @property-read \Modules\Accounting\Models\Account|null $defaultAccountsReceivable
 * @property-read \Modules\Accounting\Models\Account|null $defaultSalesDiscountAccount
 * @property-read \Modules\Accounting\Models\Account|null $defaultTaxAccount
 * @property-read Journal|null $defaultSalesJournal
 * @property-read Collection<int, \Modules\Accounting\Models\FiscalPosition> $fiscalPositions
 * @property-read int|null $fiscal_positions_count
 * @property-read Collection<int, \Modules\Sales\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read Collection<int, JournalEntry> $journalEntries
 * @property-read int|null $journal_entries_count
 * @property-read Collection<int, Journal> $journals
 * @property-read int|null $journals_count
 * @property-read Collection<int, \Modules\Accounting\Models\LockDate> $lockDates
 * @property-read int|null $lock_dates_count
 * @property-read Company|null $parentCompany
 * @property-read Collection<int, \Modules\Foundation\Models\Partner> $partners
 * @property-read int|null $partners_count
 * @property-read Collection<int, \Modules\Payment\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read Collection<int, \Modules\Product\Models\Product> $products
 * @property-read int|null $products_count
 * @property-read Collection<int, Tax> $taxes
 * @property-read int|null $taxes_count
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 * @property-read Collection<int, \Modules\Purchase\Models\VendorBill> $vendorBills
 * @property-read int|null $vendor_bills_count
 *
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
 *
 * @mixin \Eloquent
 */
#[ObservedBy([CompanyObserver::class])]
class Company extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * Carefully defining fillable attributes is paramount in a financial system
     * to prevent unauthorized mass assignment vulnerabilities and maintain
     * data integrity [1].
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'address',
        'tax_id',
        'currency_id',
        'fiscal_country',
        'parent_company_id',
        'consolidation_method',
        'enable_reconciliation',
        'default_accounts_payable_id',
        'default_tax_receivable_id',
        'default_purchase_journal_id',
        'default_accounts_receivable_id',
        'default_sales_discount_account_id',
        'default_purchase_returns_account_id',
        'default_tax_account_id',
        'default_sales_journal_id',
        'default_depreciation_journal_id',
        'default_bank_account_id',
        'default_outstanding_receipts_account_id',
        'default_bank_journal_id',
        'default_gain_loss_account_id',
        'inventory_adjustment_account_id',
        'default_stock_input_account_id',
        'default_stock_location_id',
        'default_vendor_location_id',
        'default_adjustment_location_id',
        // HR-related default accounts
        'default_salary_payable_account_id',
        'default_salary_expense_account_id',
        'default_payroll_journal_id',
        'default_income_tax_payable_account_id',
        'default_social_security_payable_account_id',
        'default_health_insurance_payable_account_id',
        'default_pension_payable_account_id',
        'default_employee_advance_receivable_account_id',
        // PDF Settings
        'pdf_template',
        'pdf_logo_path',
        'pdf_settings',
        // Numbering Settings
        'numbering_settings',
        // Inventory Settings
        'inventory_accounting_mode',
        'default_finished_goods_inventory_id',
        'default_raw_materials_inventory_id',
        'default_manufacturing_journal_id',
        'default_wip_account_id',
        // Cheque Settings
        'default_pdc_receivable_account_id',
        'default_pdc_payable_account_id',
        'default_cheque_expense_account_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // No special casts explicitly mentioned in the schema beyond default timestamps.
        // 'created_at' and 'updated_at' are Carbon instances by default.
        'enable_reconciliation' => 'boolean',
        'pdf_settings' => 'json',
        'numbering_settings' => 'json',
        'inventory_accounting_mode' => \Modules\Inventory\Enums\Inventory\InventoryAccountingMode::class,
        'consolidation_method' => \Modules\Accounting\Enums\Consolidation\ConsolidationMethod::class,
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
     */
    /**
     * @return BelongsTo<\Modules\Foundation\Models\Currency, static>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(\Modules\Foundation\Models\Currency::class);
    }

    /**
     * Get the parent company if this company is a branch or subsidiary.
     * Supports multi-branch/multi-company structures [1, 3].
     */
    /**
     * @return BelongsTo<Company, static>
     */
    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    /**
     * Get the child companies if this company is a parent.
     * This defines the hierarchical structure within the business group [1, 3].
     */
    /**
     * @return HasMany<Company, static>
     */
    public function childrenCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }

    /**
     * Get the users associated with this company.
     * In a multi-company setup, users typically belong to a specific company [1].
     */
    /**
     * @return BelongsToMany<User, static>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')->withTimestamps();
    }

    /**
     * Get the audit logs associated with actions performed within this company.
     * Comprehensive auditability is a non-negotiable principle for accounting software [1].
     */
    /**
     * @return HasMany<\Modules\Foundation\Models\AuditLog, static>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(\Modules\Foundation\Models\AuditLog::class);
    }

    /**
     * Get the lock dates configured for this company.
     * Lock dates are crucial for preventing modifications to historical financial periods [1].
     */
    /**
     * @return HasMany<\Modules\Accounting\Models\LockDate, static>
     */
    public function lockDates(): HasMany
    {
        return $this->hasMany(\Modules\Accounting\Models\LockDate::class);
    }

    /**
     * Get the chart of accounts (accounts) belonging to this company.
     * Each company maintains its own unique chart of accounts [1, 5].
     */
    /**
     * @return HasMany<\Modules\Accounting\Models\Account, static>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(\Modules\Accounting\Models\Account::class);
    }

    /**
     * Get the journals belonging to this company.
     * Journals categorize and sequence financial transactions [1, 6-8].
     */
    /**
     * @return HasMany<Journal, static>
     */
    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    /**
     * Get the journal entries posted by this company.
     * Journal entries are the immutable records of all financial transactions [1].
     */
    /**
     * @return HasMany<JournalEntry, static>
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * Get the customer invoices issued by this company.
     */
    /**
     * @return HasMany<\Modules\Sales\Models\Invoice, static>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(\Modules\Sales\Models\Invoice::class);
    }

    /**
     * Get the vendor bills received by this company.
     */
    /**
     * @return HasMany<\Modules\Purchase\Models\VendorBill, static>
     */
    public function vendorBills(): HasMany
    {
        return $this->hasMany(\Modules\Purchase\Models\VendorBill::class);
    }

    /**
     * Get the payments (inbound/outbound) processed by this company.
     */
    /**
     * @return HasMany<\Modules\Payment\Models\Payment, static>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(\Modules\Payment\Models\Payment::class);
    }

    /**
     * Get the adjustment documents (e.g., credit/debit notes) created by this company.
     */
    /**
     * @return HasMany<\Modules\Inventory\Models\AdjustmentDocument, static>
     */
    public function adjustmentDocuments(): HasMany
    {
        return $this->hasMany(\Modules\Inventory\Models\AdjustmentDocument::class);
    }

    /**
     * Get the partners (customers/vendors) associated with this company.
     * Partners can be defined per internal company [2].
     */
    /**
     * @return HasMany<\Modules\Foundation\Models\Partner, static>
     */
    public function partners(): HasMany
    {
        return $this->hasMany(\Modules\Foundation\Models\Partner::class);
    }

    /**
     * Get the products managed by this company.
     * Products can be company-specific [2].
     */
    /**
     * @return HasMany<\Modules\Product\Models\Product, static>
     */
    public function products(): HasMany
    {
        return $this->hasMany(\Modules\Product\Models\Product::class);
    }

    /**
     * Get the tax definitions for this company.
     * Taxes are configured per company [2].
     */
    /**
     * @return HasMany<Tax, static>
     */
    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    /**
     * Get the fiscal positions defined for this company.
     * Fiscal positions handle tax and account mapping based on partner location/type [2].
     */
    /**
     * @return HasMany<\Modules\Accounting\Models\FiscalPosition, static>
     */
    public function fiscalPositions(): HasMany
    {
        return $this->hasMany(\Modules\Accounting\Models\FiscalPosition::class);
    }

    /**
     * Get the fixed assets owned by this company.
     */
    /**
     * @return HasMany<\Modules\Accounting\Models\Asset, static>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(\Modules\Accounting\Models\Asset::class);
    }

    /**
     * Get the analytic accounts defined for this company.
     * Used for management/cost accounting, separate from general ledger accounts [2, 9].
     */
    /**
     * @return HasMany<\Modules\Accounting\Models\AnalyticAccount, static>
     */
    public function analyticAccounts(): HasMany
    {
        return $this->hasMany(\Modules\Accounting\Models\AnalyticAccount::class);
    }

    /**
     * Get the analytic plans defined for this company.
     * Used to group analytic accounts or define budget structures [2].
     */
    /**
     * @return HasMany<\Modules\Accounting\Models\AnalyticPlan, static>
     */
    public function analyticPlans(): HasMany
    {
        return $this->hasMany(\Modules\Accounting\Models\AnalyticPlan::class);
    }

    /**
     * Get the budgets created for this company.
     */
    /**
     * @return HasMany<\Modules\Accounting\Models\Budget, static>
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(\Modules\Accounting\Models\Budget::class);
    }

    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultAccountsPayable(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_accounts_payable_id');
    }

    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultTaxReceivable(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_tax_receivable_id');
    }

    /**
     * @return BelongsTo<Journal, static>
     */
    public function defaultPurchaseJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'default_purchase_journal_id');
    }

    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultAccountsReceivable(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_accounts_receivable_id');
    }

    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultSalesDiscountAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_sales_discount_account_id');
    }

    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultTaxAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_tax_account_id');
    }

    /**
     * Get the default Purchase Returns account.
     * This contra-expense account is credited when posting Debit Notes (vendor returns).
     *
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultPurchaseReturnsAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_purchase_returns_account_id');
    }

    /**
     * @return BelongsTo<Journal, static>
     */
    public function defaultSalesJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'default_sales_journal_id');
    }

    /**
     * @return BelongsTo<Journal, static>
     */
    public function defaultDepreciationJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'default_depreciation_journal_id');
    }

    /**
     * @return BelongsTo<Journal, static>
     */
    public function defaultBankJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'default_bank_journal_id');
    }

    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultBankAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_bank_account_id');
    }

    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultOutstandingReceiptsAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_outstanding_receipts_account_id');
    }

    /**
     * Get the default account for recording gains or losses on asset disposal.
     */
    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultGainLossAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_gain_loss_account_id');
    }

    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function inventoryAdjustmentAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'inventory_adjustment_account_id');
    }

    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultStockInputAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_stock_input_account_id');
    }

    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultWipAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_wip_account_id');
    }

    /*
    |--------------------------------------------------------------------------
    | HR-related Default Account Relationships
    |--------------------------------------------------------------------------
    */
    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultSalaryPayableAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_salary_payable_account_id');
    }

    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultSalaryExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_salary_expense_account_id');
    }

    /**
     * @return BelongsTo<Journal, static>
     */
    public function defaultPayrollJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'default_payroll_journal_id');
    }

    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultIncomeTaxPayableAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_income_tax_payable_account_id');
    }

    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultSocialSecurityPayableAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_social_security_payable_account_id');
    }

    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultHealthInsurancePayableAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_health_insurance_payable_account_id');
    }

    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultPensionPayableAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_pension_payable_account_id');
    }

    /**
     * @return BelongsTo<\Modules\Accounting\Models\Account, static>
     */
    public function defaultEmployeeAdvanceReceivableAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_employee_advance_receivable_account_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Cheque Management Default Accounts
    |--------------------------------------------------------------------------
    */

    /**
     * Get the default account for Post-Dated Cheques Receivable (Asset).
     * Used when we receive a cheque from a customer but haven't deposited it yet.
     */
    public function defaultPdcReceivableAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_pdc_receivable_account_id');
    }

    /**
     * Get the default account for Post-Dated Cheques Payable (Liability).
     * Used when we issue a cheque to a vendor but it hasn't been cleared yet.
     */
    public function defaultPdcPayableAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_pdc_payable_account_id');
    }

    /**
     * Get the default account for bank charges or cheque bounce penalties (Expense).
     */
    public function defaultChequeExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Accounting\Models\Account::class, 'default_cheque_expense_account_id');
    }

    /**
     * Check if a given date is within a locked period for the company.
     *
     * This method is essential for enforcing the immutability of posted transactions,
     * a core principle of the accounting system [1, 8]. It queries the `lock_dates`
     * table to determine if any lock date prevents transactions on the given date.
     *
     * @param  string|Carbon  $date  The date to check.
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
    /**
     * @return BelongsTo<StockLocation, static>
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
    /**
     * @return BelongsTo<StockLocation, static>
     */
    public function vendorLocation(): BelongsTo
    {
        // CORRECTED: The foreign key in the database is 'default_vendor_location_id',
        // not 'vendor_location_id'. We align the model with the schema.
        return $this->belongsTo(StockLocation::class, 'default_vendor_location_id');
    }

    /**
     * The company's default location for inventory adjustments.
     */
    /**
     * @return BelongsTo<StockLocation, static>
     */
    public function adjustmentLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'default_adjustment_location_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Numbering Settings Methods
    |--------------------------------------------------------------------------
    |
    | Methods for managing document numbering configurations.
    | These settings control how invoice and bill numbers are generated.
    |
    */

    /**
     * Get the default numbering settings structure.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getDefaultNumberingSettings(): array
    {
        return [
            'invoice' => [
                'type' => \Modules\Foundation\Enums\Settings\NumberingType::SLASH_YEAR_MONTH->value,
                'prefix' => 'INV',
                'padding' => 7,
            ],
            'vendor_bill' => [
                'type' => \Modules\Foundation\Enums\Settings\NumberingType::SLASH_YEAR_MONTH->value,
                'prefix' => 'BILL',
                'padding' => 7,
            ],
        ];
    }

    /**
     * Get the numbering settings with defaults if not set.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getNumberingSettings(): array
    {
        return $this->numbering_settings ?? $this->getDefaultNumberingSettings();
    }

    /**
     * Get invoice numbering configuration.
     *
     * @return array<string, mixed>
     */
    public function getInvoiceNumberingConfig(): array
    {
        $settings = $this->getNumberingSettings();

        return $settings['invoice'] ?? $this->getDefaultNumberingSettings()['invoice'];
    }

    /**
     * Get vendor bill numbering configuration.
     *
     * @return array<string, mixed>
     */
    public function getVendorBillNumberingConfig(): array
    {
        $settings = $this->getNumberingSettings();

        return $settings['vendor_bill'] ?? $this->getDefaultNumberingSettings()['vendor_bill'];
    }

    /**
     * Check if numbering settings can be changed.
     * Returns false if there are posted documents using current numbering.
     */
    public function canChangeNumberingSettings(): bool
    {
        // Check for posted invoices (only posted invoices have invoice_number)
        $hasPostedInvoices = $this->invoices()
            ->whereNotNull('invoice_number')
            ->exists();

        // Check for posted vendor bills (only posted bills should prevent changes)
        $hasPostedBills = $this->vendorBills()
            ->where('status', VendorBillStatus::Posted)
            ->whereNotNull('bill_reference')
            ->exists();

        return ! $hasPostedInvoices && ! $hasPostedBills;
    }

    /**
     * Get validation errors for numbering settings changes.
     *
     * @return array<int, string>
     */
    public function getNumberingChangeValidationErrors(): array
    {
        $errors = [];

        if ($this->invoices()->whereNotNull('invoice_number')->exists()) {
            $errors[] = __('foundation::numbering.validation.posted_invoices_exist');
        }

        if ($this->vendorBills()->where('status', VendorBillStatus::Posted)->whereNotNull('bill_reference')->exists()) {
            $errors[] = __('foundation::numbering.validation.posted_bills_exist');
        }

        return $errors;
    }
}
