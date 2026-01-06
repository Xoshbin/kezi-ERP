<?php

namespace Modules\Payment\Actions\Cheques;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Actions\Accounting\CreateJournalEntryForChequeAction;
use Modules\Accounting\Services\Accounting\LockDateService;
use Modules\Payment\DataTransferObjects\Cheques\ClearChequeDTO;
use Modules\Payment\Enums\Cheques\ChequeStatus;
use Modules\Payment\Models\Cheque;

class ClearChequeAction
{
    public function __construct(
        private readonly LockDateService $lockDateService,
        private readonly CreateJournalEntryForChequeAction $createJournalEntryForChequeAction,
    ) {}

    public function execute(Cheque $cheque, ClearChequeDTO $dto, User $user): void
    {
        if (! in_array($cheque->status, [ChequeStatus::HandedOver, ChequeStatus::Deposited])) {
            throw new \DomainException('Cheque must be Handed Over or Deposited to be cleared.');
        }

        $this->lockDateService->enforce($cheque->company, Carbon::parse($dto->cleared_at));

        DB::transaction(function () use ($cheque, $dto) {

            // Create Accounting Entry (Dr/Cr Bank)
            $journalEntry = $this->createJournalEntryForChequeAction->execute($cheque, 'clear');

            $cheque->update([
                'status' => ChequeStatus::Cleared,
                'cleared_at' => $dto->cleared_at,
                // We keep original journal_entry_id as the "issuance" proof,
                // or do we update? Usually we might want to track the *clearing* JE link too.
                // But Payment model only has one `journal_entry_id`.
                // Ideally, we shouldn't overwrite if we want to trace the "Issue" JE.
                // But for "Cleared", this is the one that hits the bank.
                // I won't overwrite `journal_entry_id` if it's already set from Handover/Deposit,
                // unless we add `clearing_journal_entry_id` to schema.
                // For now, I'll NOT overwrite it, but audit log will capture the new JE creation.
                // Or maybe I should. `payment->journal_entry_id` usually links to the GL impact.
                // Let's leave it as is. The returned JE is saved in DB.
            ]);
        });
    }
}
