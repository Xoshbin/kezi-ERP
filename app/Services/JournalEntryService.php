<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use Brick\Money\Money;
use App\Models\Company;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use App\Exceptions\PeriodIsLockedException;
use App\Services\Accounting\LockDateService;
use Illuminate\Validation\ValidationException;
use App\Exceptions\DeletionNotAllowedException;
use App\Actions\Accounting\ReverseJournalEntryAction;

class JournalEntryService
{
    public function __construct(protected LockDateService $lockDateService,) {}


    public function post(JournalEntry $journalEntry): bool
    {
        // If the entry is already posted, do nothing and return success.
        if ($journalEntry->is_posted) {
            return true;
        }

        // 1. Re-validate the balance before posting.
        $journalEntry->load('lines', 'currency');
        // MODIFIED: Sum Money objects from the loaded relations.
        $totalDebit = Money::of(0, $journalEntry->currency->code);
        $totalCredit = Money::of(0, $journalEntry->currency->code);
        foreach ($journalEntry->lines as $line) {
            $totalDebit = $totalDebit->plus($line->debit);
            $totalCredit = $totalCredit->plus($line->credit);
        }

        // MODIFIED: Use isEqualTo() for Money object comparison
        if (!$totalDebit->isEqualTo($totalCredit)) {
            throw ValidationException::withMessages(['lines' => 'Cannot post an unbalanced entry.']);
        }

        // Add other checks here (lock dates, etc.)

        // 2. Update the totals and post the entry.
        $journalEntry->total_debit = $totalDebit;
        $journalEntry->total_credit = $totalCredit;
        $journalEntry->is_posted = true;

        return $journalEntry->save();
    }

    /**
     * Deletes a JournalEntry if it is in draft status.
     * Deletion is blocked for posted entries to maintain financial integrity.
     *
     * @param JournalEntry $journalEntry The entry to delete.
     * @return bool|null True on successful deletion.
     * @throws DeletionNotAllowedException If the entry is already posted.
     * @throws PeriodIsLockedException If the entry's date is in a locked period.
     */
    public function delete(JournalEntry $journalEntry): ?bool
    {
        // First, check if the entry's date is in a locked period.
        // This applies to ALL entries, whether draft or posted, if their date falls within a locked period.
        $this->lockDateService->enforce($journalEntry->company, Carbon::parse($journalEntry->entry_date));


        // Block deletion if the entry has been posted.
        // Block deletion if the entry has been posted. This is the non-negotiable immutability rule.
        if ($journalEntry->is_posted) {
            throw new DeletionNotAllowedException(
                'Cannot delete a posted journal entry. Corrections must be made with a new reversal entry.'
            );
        }

        // Proceed with deletion for draft entries.
        // Using a transaction is good practice, though Eloquent's delete handles this well.
        return DB::transaction(function () use ($journalEntry) {
            // Deleting the JournalEntry will also delete its lines if foreign keys
            // are configured with `onDelete('cascade')`.
            return $journalEntry->delete();
        });
    }

    /**
     * Creates and posts a reversing journal entry for a given posted entry.
     *
     * @param JournalEntry $originalEntry The entry to be reversed.
     * @param string $reason The reason for the reversal.
     * @param User $user The user performing the action.
     * @return JournalEntry The newly created reversing entry.
     * @throws Exception
     */
    public function createReversal(JournalEntry $originalEntry, string $reason, User $user): JournalEntry
    {
        if (!$originalEntry->is_posted) {
            throw new Exception('Only posted journal entries can be reversed.');
        }

        return app(ReverseJournalEntryAction::class)->execute($originalEntry, $reason, $user);
    }
}
