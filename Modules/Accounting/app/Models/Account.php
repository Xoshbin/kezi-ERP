<?php

namespace Modules\Accounting\Models;

use Eloquent;
use App\Models\Company;
use Illuminate\Support\Carbon;
use Modules\Accounting\Models\Tax;
use Modules\Sales\Models\InvoiceLine;

use Illuminate\Database\Eloquent\Model;
use Modules\Foundation\Models\Currency;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Modules\Purchase\Models\VendorBillLine;
use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Models\JournalEntryLine;
use Modules\Accounting\Observers\AccountObserver;
use Modules\Foundation\Observers\AuditLogObserver;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Accounting\Enums\Accounting\AccountType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

/**
 * Class Account
 *
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property string|array<string, string> $name
 * @property string $type
 * @property bool $is_deprecated
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Asset> $accumulatedDepreciationAssets
 * @property-read int|null $accumulated_depreciation_assets_count
 * @property-read Collection<int, Asset> $assets
 * @property-read int|null $assets_count
 * @property-read Collection<int, BudgetLine> $budgetLines
 * @property-read int|null $budget_lines_count
 * @property-read Company $company
 * @property-read Collection<int, Asset> $depreciationExpenseAssets
 * @property-read int|null $depreciation_expense_assets_count
 * @property-read Collection<int, VendorBillLine> $expenseVendorBillLines
 * @property-read int|null $expense_vendor_bill_lines_count
 * @property-read Collection<int, InvoiceLine> $incomeInvoiceLines
 * @property-read int|null $income_invoice_lines_count
 * @property-read Collection<int, JournalEntryLine> $journalEntryLines
 * @property-read int|null $journal_entry_lines_count
 * @property-read Collection<int, FiscalPositionAccountMapping> $mappedFiscalPositionMappings
 * @property-read int|null $mapped_fiscal_position_mappings_count
 * @property-read Collection<int, FiscalPositionAccountMapping> $originalFiscalPositionMappings
 * @property-read int|null $original_fiscal_position_mappings_count
 * @property-read Collection<int, Tax> $taxes
 * @property-read int|null $taxes_count
 *
 * @method static \Modules\Accounting\Database\Factories\AccountFactory factory($count = null, $state = [])
 * @method static Builder<static>|Account newModelQuery()
 * @method static Builder<static>|Account newQuery()
 * @method static Builder<static>|Account query()
 * @method static Builder<static>|Account whereCode($value)
 * @method static Builder<static>|Account whereCompanyId($value)
 * @method static Builder<static>|Account whereCreatedAt($value)
 * @method static Builder<static>|Account whereId($value)
 * @method static Builder<static>|Account whereIsDeprecated($value)
 * @method static Builder<static>|Account whereName($value)
 * @method static Builder<static>|Account whereType($value)
 * @method static Builder<static>|Account whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
#[ObservedBy([\Modules\Accounting\Observers\AccountObserver::class, \Modules\Foundation\Observers\AuditLogObserver::class])] // (to log when accounts are created or deprecated)
class Account extends Model
{
    use HasFactory;

    protected static function newFactory(): \Modules\Accounting\Database\Factories\AccountFactory
    {
        return \Modules\Accounting\Database\Factories\AccountFactory::new();
    }
    use HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['name'];

    /**
     * Get the non-translatable fields that should be searched.
     *
     * @return array<int, string>
     */
    public function getNonTranslatableSearchFields(): array
    {
        return ['code'];
    }

    /**
     * The database table associated with the model.
     * While Eloquent conventions would automatically infer 'accounts',
     * explicit declaration can enhance clarity and prevent issues if
     * non-standard naming is ever considered [9].
     *
     * @var string
     */
    protected $table = 'accounts';

    /**
     * The attributes that are mass assignable.
     *
     * In a financial application, meticulously defining fillable attributes
     * is crucial for preventing mass assignment vulnerabilities, ensuring
     * that only expected data can be set via mass assignment operations [10].
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'currency_id',
        'code',
        'name',
        'type',
        'is_deprecated',
        'allow_reconciliation',
        'can_create_assets',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * Casting `is_deprecated` to a boolean ensures that the application
     * consistently handles this flag as a true/false value, irrespective
     * of its underlying storage type (e.g., tinyint(1)) in the database [11, 12].
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_deprecated' => 'boolean',
        'allow_reconciliation' => 'boolean',
        'type' => \Modules\Accounting\Enums\Accounting\AccountType::class, // Enums provide type safety and clarity [12]
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    |
    | Eloquent's relationship methods define how models interact with each other.
    | These relationships are paramount in constructing a coherent and navigable
    | financial data model, allowing complex queries and data retrieval with
    | elegant syntax [13, 14].
    |
    */

    /**
     * Get the currency of this invoice.
     * Every invoice operates in a specific currency. [1]
     *
     * @return BelongsTo<Currency, static>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the company that owns this account.
     * An account logically belongs to a specific company in a multi-company setup [5].
     *
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the journal entry lines associated with this account.
     *
     * Every financial transaction involves at least two journal entry lines,
     * one debit and one credit, each linked to a specific account [5].
     * This forms the bedrock of double-entry bookkeeping [15].
     *
     * @return HasMany<JournalEntryLine, static>
     */
    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Get the invoice lines where this account serves as the income account.
     *
     * Revenue recognition is tied to specific income accounts [5].
     *
     * @return HasMany<InvoiceLine, static>
     */
    public function incomeInvoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class, 'income_account_id');
    }

    /**
     * Get the vendor bill lines where this account serves as the expense account.
     *
     * Similarly, expenses are categorized under specific expense accounts [5].
     *
     * @return HasMany<VendorBillLine, static>
     */
    public function expenseVendorBillLines(): HasMany
    {
        return $this->hasMany(VendorBillLine::class, 'expense_account_id');
    }

    /**
     * Get the taxes that are linked to this account for posting tax amounts.
     *
     * Tax management often involves posting to dedicated tax liability/asset accounts [16].
     *
     * @return HasMany<Tax, static>
     */
    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class, 'tax_account_id');
    }

    /**
     * Get the fixed assets that use this account as their primary asset account.
     *
     * Fixed assets are recorded on specific balance sheet accounts [16].
     *
     * @return HasMany<Asset, static>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'asset_account_id');
    }

    /**
     * Get the fixed assets that use this account for their depreciation expense.
     *
     * Depreciation expense is typically recognized in a profit and loss account [16].
     *
     * @return HasMany<Asset, static>
     */
    public function depreciationExpenseAssets(): HasMany
    {
        return $this->hasMany(Asset::class, 'depreciation_expense_account_id');
    }

    /**
     * Get the fixed assets that use this account as their accumulated depreciation contra-asset account.
     *
     * Accumulated depreciation reduces the book value of an asset on the balance sheet [16].
     *
     * @return HasMany<Asset, static>
     */
    public function accumulatedDepreciationAssets(): HasMany
    {
        return $this->hasMany(Asset::class, 'accumulated_depreciation_account_id');
    }

    /**
     * Get the fiscal position account mappings where this account is the original account.
     *
     * Fiscal positions may remap default accounts based on specific criteria [16].
     *
     * @return HasMany<FiscalPositionAccountMapping, static>
     */
    public function originalFiscalPositionMappings(): HasMany
    {
        return $this->hasMany(FiscalPositionAccountMapping::class, 'original_account_id');
    }

    /**
     * Get the fiscal position account mappings where this account is the mapped (new) account.
     *
     * @return HasMany<FiscalPositionAccountMapping, static>
     */
    public function mappedFiscalPositionMappings(): HasMany
    {
        return $this->hasMany(FiscalPositionAccountMapping::class, 'mapped_account_id');
    }

    /**
     * Get the budget lines associated with this account.
     *
     * Accounts can be linked to financial budget lines for detailed budget vs. actual analysis [16].
     *
     * @return HasMany<BudgetLine, static>
     */
    public function budgetLines(): HasMany
    {
        return $this->hasMany(BudgetLine::class, 'account_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Business Logic Methods
    |--------------------------------------------------------------------------
    |
    | These methods encapsulate specific business rules for account management,
    | aligning with best practices for data integrity in accounting systems.
    |
    */

    /**
     * Mark the account as deprecated.
     *
     * In accounting systems, direct deletion of accounts that have associated
     * transactions is **strictly prohibited** to maintain data integrity and auditability [6-8].
     * Instead, accounts are inactivated or "deprecated" to prevent future use
     * while preserving historical records [2, 5].
     */
    public function deprecate(): bool
    {
        // One could add a check here to ensure no future-dated transactions are assigned,
        // although the lock dates mechanism should already prevent this [2, 6, 8, 17].
        $this->is_deprecated = true;

        return $this->save();
    }

    /**
     * Reactivate a deprecated account.
     *
     * Allows an account previously marked as deprecated to be used again.
     */
    public function activate(): bool
    {
        $this->is_deprecated = false;

        return $this->save();
    }

    /**
     * Determine if the account can be truly deleted (i.e., has no associated journal entries).
     *
     * This method is crucial for enforcing the immutability principle.
     * Accounts should only be physically deleted if no financial transactions
     * have ever been posted against them [6-8].
     */
    public function canBeDeleted(): bool
    {
        return $this->journalEntryLines()->doesntExist();
    }
}
