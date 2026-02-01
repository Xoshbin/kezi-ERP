<?php

namespace Jmeryar\Payment\Actions\Cheques;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jmeryar\Accounting\Actions\Accounting\CreateJournalEntryForChequeAction;
use Jmeryar\Accounting\Services\Accounting\LockDateService;
use Jmeryar\Payment\DataTransferObjects\Cheques\DepositChequeDTO;
use Jmeryar\Payment\Enums\Cheques\ChequeStatus;
use Jmeryar\Payment\Enums\Cheques\ChequeType;
use Jmeryar\Payment\Models\Cheque;

class DepositChequeAction
{
    public function __construct(
        private readonly LockDateService $lockDateService,
        private readonly CreateJournalEntryForChequeAction $createJournalEntryForChequeAction,
    ) {}

    public function execute(Cheque $cheque, DepositChequeDTO $dto, User $user): void
    {
        if ($cheque->type !== ChequeType::Receivable) {
            throw new \InvalidArgumentException('Only receivable cheques can be deposited.');
        }

        if ($cheque->status !== ChequeStatus::Draft) {
            throw new \DomainException('Cheque must be in Draft status to be deposited.');
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
