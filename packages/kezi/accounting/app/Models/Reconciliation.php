<?php

namespace Kezi\Accounting\Models;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Database\Factories\ReconciliationFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Kezi\Accounting\Enums\Reconciliation\ReconciliationType;
use RuntimeException;

/**
 * Class Reconciliation
 *
 *
 * @property int $id
 * @property int $company_id
 * @property ReconciliationType $reconciliation_type
 * @property int $reconciled_by_user_id
 * @property Carbon $reconciled_at
 * @property string|null $reference
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read User $reconciledBy
 * @property-read Collection<int, JournalEntryLine> $journalEntryLines
 */
class Reconciliation extends Model
{
    /** @use HasFactory<ReconciliationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'reconciliation_type',
        'reconciled_by_user_id',
        'reconciled_at',
        'reference',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'reconciliation_type' => ReconciliationType::class,
        'reconciled_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Automatically set the reconciled_by_user_id and reconciled_at when creating
        static::creating(function (Reconciliation $reconciliation) {
            if (Auth::check()) {
                $reconciliation->reconciled_by_user_id = (int) Auth::id();
            }
            $reconciliation->reconciled_at = now();
        });

        // Prevent modification of reconciliation records to maintain audit trail integrity
        static::updating(function (Reconciliation $reconciliation) {
            // Allow only description updates for additional notes
            $allowedFields = ['description'];
            $changedFields = array_keys($reconciliation->getDirty());
            $unauthorizedChanges = array_diff($changedFields, $allowedFields);

            if (! empty($unauthorizedChanges)) {
                throw new RuntimeException(
                    'Reconciliation records are immutable. Only description can be updated. '.
                        'Attempted to change: '.implode(', ', $unauthorizedChanges)
                );
            }
        });

        // Prevent deletion of reconciliation records
        static::deleting(function (Reconciliation $reconciliation) {
            throw new RuntimeException(
                'Reconciliation records cannot be deleted to maintain audit trail integrity. '.
                    'Create a reversal reconciliation instead.'
            );
        });
    }

    /**
     * Get the company that owns this reconciliation.
     */
    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who performed this reconciliation.
     */
    /**
     * @return BelongsTo<User, static>
     */
    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by_user_id');
    }

    /**
     * Get the journal entry lines that are part of this reconciliation.
     */
    /**
     * @return BelongsToMany<JournalEntryLine, static>
     */
    public function journalEntryLines(): BelongsToMany
    {
        return $this->belongsToMany(JournalEntryLine::class, 'journal_entry_line_reconciliation')
            ->withTimestamps();
    }

    /**
     * Check if this reconciliation is balanced (total debits = total credits).
     */
    public function isBalanced(): bool
    {
        $lines = $this->journalEntryLines;

        if ($lines->isEmpty()) {
            return true;
        }

        // Get currency from the first line
        $currency = $lines->first()->journalEntry->company->currency->code;

        // Sum the Money objects properly
        $totalDebits = $lines->reduce(function ($carry, $line) {
            return $carry->plus($line->debit);
        }, Money::of(0, $currency));

        $totalCredits = $lines->reduce(function ($carry, $line) {
            return $carry->plus($line->credit);
        }, Money::of(0, $currency));

        return $totalDebits->isEqualTo($totalCredits);
    }

    /**
     * Get the total number of lines in this reconciliation.
     */
    public function getLineCountAttribute(): int
    {
        return $this->journalEntryLines()->count();
    }
}
