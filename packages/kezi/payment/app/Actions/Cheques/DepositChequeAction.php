<?php

namespace Kezi\Payment\Actions\Cheques;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Actions\Accounting\CreateJournalEntryForChequeAction;
use Kezi\Accounting\Services\Accounting\LockDateService;
use Kezi\Payment\DataTransferObjects\Cheques\DepositChequeDTO;
use Kezi\Payment\Enums\Cheques\ChequeStatus;
use Kezi\Payment\Enums\Cheques\ChequeType;
use Kezi\Payment\Models\Cheque;

class DepositChequeAction
{
    public function __construct(
        private readonly LockDateService $lockDateService,
        private readonly CreateJournalEntryForChequeAction $createJournalEntryForChequeAction,
    ) {}

    public function execute(Cheque $cheque, DepositChequeDTO $dto, User $user): void
    {
        if ($cheque->type !== ChequeType::Receivable) {
            throw new \InvalidArgumentException(__('payment::exceptions.cheque.receivable_only_for_deposit'));
        }

        if ($cheque->status !== ChequeStatus::Draft) {
            throw new \DomainException(__('payment::exceptions.cheque.must_be_draft_to_deposit'));
        }

        $this->lockDateService->enforce($cheque->company, Carbon::parse($dto->deposited_at));

        DB::transaction(function () use ($cheque, $dto) {
            $cheque->update([
                'status' => ChequeStatus::Deposited,
                'deposited_at' => $dto->deposited_at,
            ]);

            // Create Accounting Entry (Dr Cheques in Collection, Cr AR)
            $journalEntry = $this->createJournalEntryForChequeAction->execute($cheque, 'deposit');

            if ($journalEntry) {
                $cheque->update(['journal_entry_id' => $journalEntry->id]);
            }
        });
    }
}
