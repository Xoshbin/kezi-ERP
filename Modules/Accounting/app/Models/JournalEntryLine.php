<?php

namespace Modules\Accounting\Models;

use App\Casts\BaseCurrencyMoneyCast;
use App\Casts\OriginalCurrencyMoneyCast;
use App\Observers\JournalEntryLineObserver;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon; // Utilized for explicit enforcement of immutability and data integrity.
use RuntimeException;

/**
 * Class JournalEntryLine
 *
 * @property int $id
 * @property int $journal_entry_id
 * @property int $account_id
 * @property int|null $partner_id
 * @property int|null $currency_id
 * @property int|null $analytic_account_id
 * @property Money $debit
 * @property Money $credit
 * @property Money $original_currency_amount
 * @property float $exchange_rate_at_transaction
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Account $account
 * @property-read AnalyticAccount|null $analyticAccount
 * @property-read JournalEntry $journalEntry
 * @property-read Partner|null $partner
 *
 * @method static Builder<static>|JournalEntryLine newModelQuery()
 * @method static Builder<static>|JournalEntryLine newQuery()
 * @method static Builder<static>|JournalEntryLine query()
 * @method static Builder<static>|JournalEntryLine whereAccountId($value)
 * @method static Builder<static>|JournalEntryLine whereAnalyticAccountId($value)
 * @method static Builder<static>|JournalEntryLine whereCreatedAt($value)
 * @method static Builder<static>|JournalEntryLine whereCredit($value)
 * @method static Builder<static>|JournalEntryLine whereCurrencyId($value)
 * @method static Builder<static>|JournalEntryLine whereDebit($value)
 * @method static Builder<static>|JournalEntryLine whereDescription($value)
 * @method static Builder<static>|JournalEntryLine whereExchangeRateAtTransaction($value)
 * @method static Builder<static>|JournalEntryLine whereId($value)
 * @method static Builder<static>|JournalEntryLine whereJournalEntryId($value)
 * @method static Builder<static>|JournalEntryLine whereOriginalCurrencyAmount($value)
 * @method static Builder<static>|JournalEntryLine wherePartnerId($value)
 * @method static Builder<static>|JournalEntryLine whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[ObservedBy([JournalEntryLineObserver::class])]
class JournalEntryLine extends Model
{
    /** @use HasFactory<\Database\Factories\JournalEntryLineFactory> */
    use HasFactory;

    /**
     * The database table associated with the model.
     *
     * Explicitly declares the table name to prevent potential pluralization mismatches from Eloquent's conventions.
     *
     * @var string
     */
    protected $table = 'journal_entry_lines';

    /**
     * The attributes that are mass assignable.
     *
     * These fields are permissible for mass assignment operations, allowing for efficient data population.
     * Critical integrity-related fields (e.g., those implying 'posted' status or calculated totals) are omitted
     * as they should be managed programmatically by the application's business logic, not direct user input.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'journal_entry_id',
        'account_id',
        'partner_id',
        'currency_id',
        'debit',
        'credit',
        'description',
        'analytic_account_id',
        'original_currency_amount',
        'original_currency_id',
        'exchange_rate_at_transaction',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * Casting ensures that data retrieved from the database is consistently presented in appropriate PHP types.
     * Financial amounts (`debit`, `credit`) are rigorously cast to `decimal:2` to maintain currency precision.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // These fields are ALWAYS in the company's base currency
        'debit' => BaseCurrencyMoneyCast::class,
        'credit' => BaseCurrencyMoneyCast::class,

        // This field is in the foreign currency
        'original_currency_amount' => OriginalCurrencyMoneyCast::class,

        // Cast the rate for correct handling
        'exchange_rate_at_transaction' => 'float',
    ];

    /**
     * The relationships that should always be loaded.
     * Eager-loading the `journalEntry.company.currency` relationship is critical because the `BaseCurrencyMoneyCast`
     * for monetary fields on this model depends on the currency context provided by the parent journal entry's company.
     * Without this, any retrieval of a `JournalEntryLine` would fail when casting monetary values
     * due to the missing currency information, leading to a "currency_id on null" error.
     *
     * @var list<string>
     */
    protected $with = ['journalEntry.company.currency'];

    /**
     * The "booted" method of the model.
     *
     * This pivotal static method is invoked once the model has been initialized. It serves as a critical
     * enforcement point for core accounting principles, particularly the **immutability of posted financial records** [1-4].
     * By preventing direct modification or deletion of lines belonging to posted journal entries,
     * we reinforce data integrity and ensure an auditable financial history [1-4].
     */
    protected static function booted(): void
    {
        // Enforce data integrity: A journal entry line must have either a debit or a credit, but not both,
        // and both must be non-negative. It cannot have both as zero.

        // Crucial immutability enforcement: Prevent modification of a journal entry line
        // if its parent journal entry has already been posted.
        static::updating(function (JournalEntryLine $line) {
            // Retrieve the parent JournalEntry to check its `is_posted` status from the database.
            // Using `fresh()` or a direct query (`first()`) ensures we operate on the most current state,
            // avoiding potential stale in-memory data for this critical check.
            $parent = $line->journalEntry()->first();
            if ($parent instanceof JournalEntry && $parent->is_posted) {
                // Defines the specific fields within the JournalEntryLine that are considered
                // immutable once the parent JournalEntry is posted. This covers all financial and linking data.
                $immutableFields = [
                    'journal_entry_id',
                    'account_id',
                    'debit',
                    'credit',
                    'description',
                    'partner_id',
                    'analytic_account_id',
                ];

                foreach ($immutableFields as $field) {
                    // Detect if any critical field has been attempted to be modified.
                    if ($line->isDirty($field)) {
                        // Throw a RuntimeException to immediately halt the operation,
                        // emphasizing that direct alteration of posted financial records is prohibited [1-3].
                        throw new RuntimeException(
                            "Attempted to modify immutable journal entry line field: '{$field}'. ".
                                'The parent journal entry is already posted. Corrections to posted financial records '.
                                'must be made exclusively via new, offsetting contra-entries at the parent entry level [1-4].'
                        );
                    }
                }
            }
        });

        // Critical immutability enforcement: Prevent deletion of any journal entry line
        // if its parent journal entry has already been posted.
        static::deleting(function (JournalEntryLine $line) {
            $parent = $line->journalEntry()->first();
            if ($parent instanceof JournalEntry && $parent->is_posted) {
                // Similar to updates, deletions are strictly disallowed for posted records,
                // enforcing that financial history remains complete and auditable [1-3].
                throw new RuntimeException(
                    'Cannot delete a journal entry line because its parent journal entry is already posted. '.
                        'Financial records are immutable. Corrections must be made via new, offsetting contra-entries [1-4].'
                );
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    |
    | Eloquent relationships define how this model interacts with other models,
    | providing a fluent and intuitive interface for traversing related data.
    |
    */

    /**
     * Get the company that this rate belongs to.
     */
    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the parent `JournalEntry` model that this line belongs to.
     *
     * This `belongsTo` relationship is foundational, linking each line to its overarching transaction.
     *
     * @return BelongsTo An Eloquent relationship instance for the `JournalEntry` model.
     */
    /**
     * @return BelongsTo<JournalEntry, static>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Get the `Account` model that this journal entry line affects.
     *
     * Each line impacts a specific account within the chart of accounts, crucial for detailed ledger postings.
     *
     * @return BelongsTo An Eloquent relationship instance for the `Account` model.
     */
    /**
     * @return BelongsTo<Account, static>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the `Partner` (customer or vendor) model optionally associated with this line.
     *
     * This relationship supports tracking transactions involving specific external entities.
     *
     * @return BelongsTo An Eloquent relationship instance for the `Partner` model.
     */
    /**
     * @return BelongsTo<Partner, static>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the `AnalyticAccount` model optionally associated with this line.
     *
     * Used for management accounting, allowing cost and revenue tracking against projects, departments, or other dimensions.
     *
     * @return BelongsTo An Eloquent relationship instance for the `AnalyticAccount` model.
     */
    /**
     * @return BelongsTo<AnalyticAccount, static>
     */
    public function analyticAccount(): BelongsTo
    {
        return $this->belongsTo(AnalyticAccount::class);
    }

    /**
     * Get the `Currency` model for this journal entry line.
     *
     * This relationship supports multi-currency transactions where individual lines
     * may have different currencies than the parent journal entry.
     *
     * @return BelongsTo An Eloquent relationship instance for the `Currency` model.
     */
    /**
     * @return BelongsTo<Currency, static>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Relationship to the original currency for this line.
     */
    /**
     * @return BelongsTo<Currency, static>
     */
    public function originalCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'original_currency_id');
    }

    /**
     * Get the reconciliations that this journal entry line is part of.
     *
     * A journal entry line can be part of multiple reconciliations over time
     * (e.g., if a reconciliation is reversed and a new one is created).
     */
    /**
     * @return BelongsToMany<Reconciliation, static>
     */
    public function reconciliations(): BelongsToMany
    {
        return $this->belongsToMany(Reconciliation::class, 'journal_entry_line_reconciliation')
            ->withTimestamps();
    }

    /**
     * Check if this journal entry line is currently reconciled.
     */
    public function isReconciled(): bool
    {
        return $this->reconciliations()->exists();
    }

    /**
     * Get the current active reconciliation for this line.
     * Returns the most recent reconciliation if multiple exist.
     */
    public function currentReconciliation(): ?Reconciliation
    {
        /** @var Reconciliation|null $reconciliation */
        $reconciliation = $this->reconciliations()->latest('reconciled_at')->first();

        return $reconciliation;
    }
}
