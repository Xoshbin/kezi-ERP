<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Observers\AuditLogObserver;
use App\Observers\JournalEntryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException; // For explicit exception handling for immutability violations

/**
 * Class JournalEntry
 * @package App\Models
 *
 * This Eloquent model represents a financial journal entry in the double-entry accounting system.
 * It serves as the immutable record of all posted financial transactions [1-3].
 *
 * @property int $id The primary key, a sequentially numbered identifier for all posted financial transactions [3].
 * @property int $company_id Foreign key to the company this entry belongs to [3].
 * @property int $journal_id Foreign key to the journal (e.g., Sales, Bank, Miscellaneous) this entry belongs to [3].
 * @property string $entry_date The accounting date of the transaction [3, 5, 6].
 * @property string $reference A unique reference number (e.g., invoice number, bill reference) [3, 5, 6].
 * @property string $description A summary of the entire transaction [3].
 * @property float $total_debit The calculated sum of all debit lines, must equal total_credit [2, 3, 7].
 * @property float $total_credit The calculated sum of all credit lines, must equal total_debit [2, 3, 7].
 * @property bool $is_posted A boolean flag indicating if the entry is posted. Once true, this record is considered immutable [3].
 * @property string $hash A cryptographic fingerprint (SHA-256) of the entry's essential data, crucial for inalterability verification [3, 4, 8-10].
 * @property string $previous_hash The hash of the immediately preceding journal entry, forming a blockchain-like audit chain [3, 4, 8, 9].
 * @property int $created_by_user_id Foreign key to the user who created this entry [3].
 * @property string|null $source_type Polymorphic relation: model class name of the originating document (e.g., 'App\Models\Invoice') [3].
 * @property int|null $source_id Polymorphic relation: ID of the originating document [3].
 * @property \Illuminate\Support\Carbon $created_at The actual system creation date/time (immutable once set), vital for audit trails [3, 4, 8, 9].
 * @property \Illuminate\Support\Carbon $updated_at Timestamp for last update (managed by Eloquent, but content should not change if posted) [11].
 *
 * @property-read \App\Models\Company $company The company associated with this journal entry.
 * @property-read \App\Models\Journal $journal The journal where this entry was recorded.
 * @property-read \App\Models\User $createdBy The user who initiated the creation of this journal entry.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\JournalEntryLine[] $lines The individual debit/credit lines composing this journal entry.
 * @property-read \Illuminate\Models\Model|\Eloquent $source The originating document (e.g., Invoice, VendorBill, Payment) for this journal entry.
 */
#[ObservedBy([JournalEntryObserver::class, AuditLogObserver::class])]
class JournalEntry extends Model
{
    use HasFactory;
    /**
     * The database table associated with the model.
     *
     * Explicitly defining the table name for clarity, though Eloquent convention often handles this [12].
     *
     * @var string
     */
    protected $table = 'journal_entries';

    /**
     * The attributes that are mass assignable.
     *
     * These are the fields that can be safely set via mass assignment [13, 14].
     * Fields related to financial integrity and auditability like `is_posted`, `hash`,
     * `previous_hash`, `total_debit`, `total_credit`, and `created_by_user_id`
     * are managed programmatically and are excluded from mass assignment to prevent vulnerabilities
     * and enforce strict data integrity rules [3].
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'journal_id',
        'entry_date',
        'reference',
        'description',
        'source_type',
        'source_id',
        'total_debit',
        'total_credit',
        'is_posted',
        'hash',
        'previous_hash',
        'created_by_user_id',
        'currency_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * Casting ensures that database values are converted to appropriate PHP data types [15].
     * Dates are cast to Carbon instances [16]. Decimal values for financial amounts
     * are cast with a precision of 2 for currency consistency [3, 17].
     * The `is_posted` flag is a boolean [3].
     *
     * @var array<string, string>
     */
    protected $casts = [
        'entry_date' => 'date',
        'total_debit' => MoneyCast::class, // Represents currency, typically 2 decimal places for financial accuracy [3, 17].
        'total_credit' => MoneyCast::class, // Represents currency, typically 2 decimal places [3, 17].
        'is_posted' => 'boolean', // Crucial flag for immutability [3].
    ];

    /**
     * The "booted" method of the model.
     *
     * This static method is invoked once the model has been booted, providing a hook
     * to apply global logic such as event listeners to enforce core accounting principles [18, 19].
     *
     * @return void
     */
    protected static function booted(): void
    {
        // Enforce the assignment of the creator's user ID upon initial creation,
        // vital for maintaining an accurate audit trail [3, 4, 8].
        static::creating(function (JournalEntry $journalEntry) {
            if (auth()->check()) {
                $journalEntry->created_by_user_id = auth()->id();
            }
            // `is_posted` is typically initialized to `false` in the migration,
            // and transitions to `true` via a dedicated posting mechanism in the application's
            // service layer, which also handles hashing.
        });

        // Strict enforcement of immutability for posted financial records.
        // Direct modification or deletion of posted journal entries is explicitly disallowed
        // to maintain data integrity and compliance with accounting standards [1-3, 7].
        // Corrections must exclusively be handled via new contra-entries [1, 2, 4].
        static::updating(function (JournalEntry $journalEntry) {
            // If the entry is already marked as 'posted' (i.e., `is_posted` was already `true`),
            // prevent any modification to its core financial data fields.
            if ($journalEntry->getOriginal('is_posted') === true) {
                $financialFields = [
                    'company_id',
                    'journal_id',
                    'entry_date',
                    'reference',
                    'description',
                    'total_debit',
                    'total_credit',
                    'hash',
                    'previous_hash',
                    'source_type',
                    'source_id',
                    'created_by_user_id',
                    'created_at' // Created_at is also considered immutable once set [3].
                ];

                foreach ($financialFields as $field) {
                    if ($journalEntry->isDirty($field)) {
                        // A RuntimeException is thrown to immediately halt any attempt
                        // to alter an immutable financial record via the ORM, reinforcing the principle.
                        throw new RuntimeException("Attempted to modify immutable posted journal entry field: '{$field}'. Corrections must be made via new, offsetting contra-entries.");
                    }
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    |
    | Eloquent relationships define how models interact with each other in the database,
    | providing a fluent and intuitive way to manage related data [20, 21].
    |
    */

    /**
     * Get the Company model that owns this journal entry.
     *
     * @return BelongsTo An Eloquent relationship instance for the Company model [3, 22].
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the Journal model to which this entry belongs.
     *
     * @return BelongsTo An Eloquent relationship instance for the Journal model [3, 22].
     */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * Get the User model who created this journal entry.
     *
     * @return BelongsTo An Eloquent relationship instance for the User model, specifying the foreign key [3, 22].
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get all of the JournalEntryLine models associated with this journal entry.
     *
     * A single journal entry is composed of multiple individual debit and credit lines [3].
     *
     * @return HasMany An Eloquent relationship instance for the JournalEntryLine model [3, 23].
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Get the parent source model (e.g., Invoice, VendorBill, Payment, AdjustmentDocument)
     * that originated or is directly associated with this journal entry.
     *
     * This is a polymorphic relationship, allowing the journal entry to link to various
     * types of source documents through `source_type` (model class name) and `source_id` (model ID) [3, 24].
     *
     * @return MorphTo An Eloquent polymorphic relationship instance [3, 24].
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    |
    | These methods provide utility functions related to the JournalEntry model's
    | accounting characteristics.
    |
    */

    /**
     * Calculates and sets the total debit and total credit amounts for this journal entry
     * by summing the debit and credit values from its associated lines.
     *
     * This method is crucial to ensure the fundamental double-entry accounting equation (Debit = Credit)
     * is met at the entry level before an entry is posted [2, 3, 7].
     * This calculation should ideally be performed and validated in the business logic layer
     * before marking the entry as 'posted'.
     *
     * @return void
     */
    public function calculateTotalsFromLines(): void
    {
        $this->total_debit = $this->lines()->sum('debit');
        $this->total_credit = $this->lines()->sum('credit');
        // Further validation (e.g., ensuring total_debit === total_credit) should occur
        // in the service layer or a dedicated validation rule [3].
    }
}
