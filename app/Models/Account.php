<?php

namespace App\Models;

use App\Observers\AccountObserver;
use App\Observers\AuditLogObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Account
 *
 * @package App\Models
 *
 * This Eloquent model represents an account within the Chart of Accounts.
 * It is designed with core accounting principles in mind, such as immutability
 * for financial transactions and robust auditability. Accounts, once used
 * in a financial transaction, cannot be deleted but can be marked as deprecated.
 *
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property bool $is_deprecated
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */

#[ObservedBy([AccountObserver::class, AuditLogObserver::class])] //(to log when accounts are created or deprecated)
class Account extends Model
{
    use HasFactory;

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
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'is_deprecated',
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
     * Get the company that owns this account.
     * An account logically belongs to a specific company in a multi-company setup [5].
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function originalFiscalPositionMappings(): HasMany
    {
        return $this->hasMany(FiscalPositionAccountMapping::class, 'original_account_id');
    }

    /**
     * Get the fiscal position account mappings where this account is the mapped (new) account.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
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
     *
     * @return bool
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
     *
     * @return bool
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
     *
     * @return bool
     */
    public function canBeDeleted(): bool
    {
        return $this->journalEntryLines()->doesntExist();
    }
}
