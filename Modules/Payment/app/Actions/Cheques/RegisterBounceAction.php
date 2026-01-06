<?php

namespace Modules\Payment\Actions\Cheques;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Payment\DataTransferObjects\Cheques\BounceChequeDTO;
use Modules\Payment\Enums\Cheques\ChequeStatus;
use Modules\Payment\Models\Cheque;
use Modules\Payment\Models\ChequeBouncedLog;

class RegisterBounceAction
{
    public function __construct(
        private readonly JournalEntryService $journalEntryService,
    ) {}

    public function execute(Cheque $cheque, BounceChequeDTO $dto, User $user): void
    {
        if (! in_array($cheque->status, [ChequeStatus::HandedOver, ChequeStatus::Deposited])) {
            throw new \DomainException('Cheque must be active (Handed Over/Deposited) to bounce.');
        }

        DB::transaction(function () use ($cheque, $dto, $user) {

            // 1. Reverse the previous Journal Entry (Handover or Deposit)
            if ($cheque->journal_entry_id) {
                $originalEntry = $cheque->journalEntry;
                $this->journalEntryService->createReversal(
                    $originalEntry,
                    "Cheque {$cheque->cheque_number} Bounced: ".$dto->reason,
                    $user
                );
            }

            // 2. Log bounce
            ChequeBouncedLog::create([
                'cheque_id' => $cheque->id,
                'bounced_at' => $dto->bounced_at,
                'reason' => $dto->reason,
                'bank_charges' => $dto->bank_charges,
                'notes' => $dto->notes,
            ]);

            // 3. Update Status
            $cheque->update([
                'status' => ChequeStatus::Bounced,
                'bounced_at' => $dto->bounced_at,
                // We typically DO NOT clear the journal_entry_id, so we know what was reversed.
            ]);

            // 4. Handle Bank Charges if present (Optional Expense)
            if ($dto->bank_charges && $dto->bank_charges->isPositive()) {
                // Future: Create separate Journal Entry for charges using CreateJournalEntryAction
            }
        });
    }
}
