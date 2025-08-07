<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\UpdateJournalEntryDTO;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\Currency;
use App\Models\JournalEntry;
use App\Services\AccountingValidationService;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateJournalEntryAction
{
    public function __construct(
        private readonly AccountingValidationService $validationService = new AccountingValidationService()
    ) {}

    public function execute(UpdateJournalEntryDTO $dto): JournalEntry
    {
        $journalEntry = $dto->journalEntry;

        // 1. Perform all necessary validation before touching the database.
        $this->validationService->checkIfPeriodIsLocked($journalEntry->company_id, $dto->entry_date);

        if ($journalEntry->is_posted) {
            throw new UpdateNotAllowedException('Cannot modify a posted journal entry.');
        }

        $currency = Currency::find($dto->currency_id);
        $totalDebit = Money::zero($currency->code);
        $totalCredit = Money::zero($currency->code);

        foreach ($dto->lines as $line) {
            $totalDebit = $totalDebit->plus(Money::of($line->debit, $currency->code));
            $totalCredit = $totalCredit->plus(Money::of($line->credit, $currency->code));
        }

        if (!$totalDebit->isEqualTo($totalCredit)) {
            throw ValidationException::withMessages([
                'lines' => 'The total debits must equal the total credits.',
            ]);
        }

        // 2. Perform the update within a database transaction.
        return DB::transaction(function () use ($dto, $journalEntry, $currency) {
            // Update the parent model's main fields
            $journalEntry->update([
                'journal_id' => $dto->journal_id,
                'entry_date' => $dto->entry_date,
                'reference' => $dto->reference,
                'description' => $dto->description,
                'is_posted' => $dto->is_posted,
            ]);

            // Sync the lines: delete the old ones
            $journalEntry->lines()->delete();

            // Create the new lines from the DTO
            if (!empty($dto->lines)) {
                foreach ($dto->lines as $lineDto) {
                    $line = new \App\Models\JournalEntryLine();

                    // First, establish the relationship. This makes the parent's context (like currency)
                    // available to the line model *before* any attributes are set. This is the key
                    // to solving the MoneyCast issue without schema changes.
                    $line->journalEntry()->associate($journalEntry);

                    // Now, fill the attributes. The MoneyCast on 'debit' and 'credit' will be
                    // triggered here, but it can now successfully call getCurrencyIdAttribute()
                    // because the journalEntry relationship is established.
                    $line->fill([
                        'account_id' => $lineDto->account_id,
                        'partner_id' => $lineDto->partner_id,
                        'analytic_account_id' => $lineDto->analytic_account_id,
                        'description' => $lineDto->description,
                        'debit' => Money::of($lineDto->debit, $currency->code),
                        'credit' => Money::of($lineDto->credit, $currency->code),
                    ]);

                    // Finally, save the fully prepared line.
                    $line->save();
                }
            }

            // Recalculate totals from the new lines and save
            $journalEntry->calculateTotalsFromLines();
            $journalEntry->save();

            return $journalEntry;
        });
    }
}
