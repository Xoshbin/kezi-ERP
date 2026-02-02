<?php

namespace Kezi\Payment\Actions\PettyCash;

use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Kezi\Accounting\Services\Accounting\LockDateService;
use Kezi\Foundation\Services\SequenceService;
use Kezi\Payment\DataTransferObjects\PettyCash\CreatePettyCashReplenishmentDTO;
use Kezi\Payment\Enums\PettyCash\PettyCashFundStatus;
use Kezi\Payment\Models\PettyCash\PettyCashReplenishment;

class CreatePettyCashReplenishmentAction
{
    public function __construct(
        private readonly SequenceService $sequenceService,
        private readonly CreateJournalEntryAction $createJournalEntryAction,
        private readonly LockDateService $lockDateService,
    ) {}

    public function execute(CreatePettyCashReplenishmentDTO $dto, User $user): PettyCashReplenishment
    {
        return DB::transaction(function () use ($dto, $user) {
            $fund = \Kezi\Payment\Models\PettyCash\PettyCashFund::findOrFail($dto->fund_id);

            // Validate fund is active
            if ($fund->status !== PettyCashFundStatus::Active) {
                throw new \InvalidArgumentException('Cannot replenish a closed fund.');
            }

            // Check lock date
            $company = $fund->company;
            $this->lockDateService->enforce($company, Carbon::parse($dto->replenishment_date));

            // Generate replenishment number
            $replenishmentNumber = $this->sequenceService->getNextNumber(
                $dto->company_id,
                'petty_cash_replenishment'
            );

            // Create journal entry: Dr Petty Cash / Cr Bank
            $lines = [
                new CreateJournalEntryLineDTO(
                    account_id: $fund->account_id,
                    debit: $dto->amount,
                    credit: Money::zero($fund->currency->code),
                    description: "Petty Cash Replenishment - {$fund->name}",
                    partner_id: null,
                    analytic_account_id: null,
                ),
                new CreateJournalEntryLineDTO(
                    account_id: $fund->bank_account_id,
                    debit: Money::zero($fund->currency->code),
                    credit: $dto->amount,
                    description: "Petty Cash Replenishment - {$fund->name}",
                    partner_id: null,
                    analytic_account_id: null,
                ),
            ];

            $journalDTO = new CreateJournalEntryDTO(
                company_id: $dto->company_id,
                journal_id: $company->default_cash_journal_id,
                currency_id: $fund->currency_id,
                entry_date: $dto->replenishment_date,
                reference: $replenishmentNumber,
                description: "Petty Cash Replenishment - {$fund->name}",
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lines,
                source_type: PettyCashReplenishment::class,
                source_id: null, // Will be updated after creation
            );

            $journalEntry = $this->createJournalEntryAction->execute($journalDTO);

            // Create replenishment record
            $replenishment = PettyCashReplenishment::create([
                'company_id' => $dto->company_id,
                'fund_id' => $dto->fund_id,
                'replenishment_number' => $replenishmentNumber,
                'amount' => $dto->amount,
                'replenishment_date' => $dto->replenishment_date,
                'payment_method' => $dto->payment_method,
                'reference' => $dto->reference,
                'journal_entry_id' => $journalEntry->id,
            ]);

            // Update journal entry source_id
            $journalEntry->update(['source_id' => $replenishment->id]);

            // Update fund balance
            $fund->update([
                'current_balance' => $fund->current_balance->plus($dto->amount),
            ]);

            return $replenishment;
        });
    }
}
