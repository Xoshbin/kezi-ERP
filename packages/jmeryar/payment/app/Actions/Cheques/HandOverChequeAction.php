<?php

namespace Jmeryar\Payment\Actions\Cheques;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jmeryar\Accounting\Actions\Accounting\CreateJournalEntryForChequeAction;
use Jmeryar\Accounting\Services\Accounting\LockDateService;
use Jmeryar\Payment\Enums\Cheques\ChequeStatus;
use Jmeryar\Payment\Enums\Cheques\ChequeType;
use Jmeryar\Payment\Models\Cheque;

class HandOverChequeAction
{
    public function __construct(
        private readonly LockDateService $lockDateService,
        private readonly CreateJournalEntryForChequeAction $createJournalEntryForChequeAction,
    ) {}

    public function execute(Cheque $cheque, User $user): void
    {
        if ($cheque->type !== ChequeType::Payable) {
            throw new \InvalidArgumentException('Only payable cheques can be handed over.');
        }

        if (! in_array($cheque->status, [ChequeStatus::Draft, ChequeStatus::Printed])) {
            throw new \DomainException('Cheque must be in Draft or Printed status to be handed over.');
        }

        $this->lockDateService->enforce($cheque->company, Carbon::now());

        DB::transaction(function () use ($cheque) {
            $cheque->update([
                'status' => ChequeStatus::HandedOver,
            ]);

            // Create Accounting Entry (Dr AP, Cr Outstanding Cheques)
            $journalEntry = $this->createJournalEntryForChequeAction->execute($cheque, 'handover');

            if ($journalEntry) {
                $cheque->update(['journal_entry_id' => $journalEntry->id]);
            }
        });
    }
}
