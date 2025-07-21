<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Observers\CompanyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

/**
 * Class Company
 *
 * @package App\Models
 *
 * This Eloquent model represents a distinct legal entity or branch within the
 * multi-company accounting system. It serves as the root for all financial
 * data and configurations, ensuring proper segregation and adherence to
 * specific fiscal requirements.
 *
 * @property int $id
 * @property string $name The legal name of the company.
 * @property string|null $address The company's physical address.
 * @property string|null $tax_id The company's tax identification number (e.g., VAT number, Iraqi tax ID).
 * @property int $currency_id Foreign Key to currencies.id, the default operating currency for the company.
 * @property string $fiscal_country The fiscal country code (e.g., 'IQ' for Iraq), crucial for localization and tax compliance.
 * @property int|null $parent_company_id Nullable Foreign Key to companies.id, supporting multi-branch/multi-company structures.
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Currency $currency
 * @property-read \App\Models\Company|null $parentCompany
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Company[] $childrenCompanies
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AuditLog[] $auditLogs
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\LockDate[] $lockDates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Account[] $accounts
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Journal[] $journals
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\JournalEntry[] $journalEntries
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Invoice[] $invoices
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VendorBill[] $vendorBills
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Payment[] $payments
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AdjustmentDocument[] $adjustmentDocuments
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Partner[] $partners
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tax[] $taxes
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FiscalPosition[] $fiscalPositions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Asset[] $assets
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AnalyticAccount[] $analyticAccounts
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AnalyticPlan[] $analyticPlans
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Budget[] $budgets
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
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // No special casts explicitly mentioned in the schema beyond default timestamps.
        // 'created_at' and 'updated_at' are Carbon instances by default.
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the parent company if this company is a branch or subsidiary.
     * Supports multi-branch/multi-company structures [1, 3].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    /**
     * Get the child companies if this company is a parent.
     * This defines the hierarchical structure within the business group [1, 3].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function childrenCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }

    /**
     * Get the users associated with this company.
     * In a multi-company setup, users typically belong to a specific company [1].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the audit logs associated with actions performed within this company.
     * Comprehensive auditability is a non-negotiable principle for accounting software [1].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get the lock dates configured for this company.
     * Lock dates are crucial for preventing modifications to historical financial periods [1].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lockDates(): HasMany
    {
        return $this->hasMany(LockDate::class);
    }

    /**
     * Get the chart of accounts (accounts) belonging to this company.
     * Each company maintains its own unique chart of accounts [1, 5].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Get the journals belonging to this company.
     * Journals categorize and sequence financial transactions [1, 6-8].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    /**
     * Get the journal entries posted by this company.
     * Journal entries are the immutable records of all financial transactions [1].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * Get the customer invoices issued by this company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the vendor bills received by this company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vendorBills(): HasMany
    {
        return $this->hasMany(VendorBill::class);
    }

    /**
     * Get the payments (inbound/outbound) processed by this company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the adjustment documents (e.g., credit/debit notes) created by this company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function adjustmentDocuments(): HasMany
    {
        return $this->hasMany(AdjustmentDocument::class);
    }

    /**
     * Get the partners (customers/vendors) associated with this company.
     * Partners can be defined per internal company [2].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class);
    }

    /**
     * Get the products managed by this company.
     * Products can be company-specific [2].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the tax definitions for this company.
     * Taxes are configured per company [2].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    /**
     * Get the fiscal positions defined for this company.
     * Fiscal positions handle tax and account mapping based on partner location/type [2].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fiscalPositions(): HasMany
    {
        return $this->hasMany(FiscalPosition::class);
    }

    /**
     * Get the fixed assets owned by this company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Get the analytic accounts defined for this company.
     * Used for management/cost accounting, separate from general ledger accounts [2, 9].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function analyticAccounts(): HasMany
    {
        return $this->hasMany(AnalyticAccount::class);
    }

    /**
     * Get the analytic plans defined for this company.
     * Used to group analytic accounts or define budget structures [2].
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function analyticPlans(): HasMany
    {
        return $this->hasMany(AnalyticPlan::class);
    }

    /**
     * Get the budgets created for this company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }
}
