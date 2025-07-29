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

        $totalDebit = collect($dto->lines)->sum(fn($line) => $line->debit);
        $totalCredit = collect($dto->lines)->sum(fn($line) => $line->credit);

        if (bccomp((string)$totalDebit, (string)$totalCredit, 2) !== 0) {
            throw ValidationException::withMessages([
                'lines' => 'The total debits must equal the total credits.',
            ]);
        }

        // 2. Perform the update within a database transaction.
        return DB::transaction(function () use ($dto, $journalEntry) {
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
                $currency = Currency::find($dto->currency_id);
                $currencyCode = $currency->code;

                $linesToCreate = [];
                foreach ($dto->lines as $lineDto) {
                    $linesToCreate[] = [
                        'account_id' => $lineDto->account_id,
                        'partner_id' => $lineDto->partner_id,
                        'analytic_account_id' => $lineDto->analytic_account_id,
                        'description' => $lineDto->description,
                        'debit' => Money::of($lineDto->debit, $currencyCode),
                        'credit' => Money::of($lineDto->credit, $currencyCode),
                        'currency_id' => $dto->currency_id,
                    ];
                }

                $journalEntry->lines()->createMany($linesToCreate);
            }

            // Recalculate totals from the new lines and save
            $journalEntry->calculateTotalsFromLines();
            $journalEntry->save();

            return $journalEntry;
        });
    }
}
