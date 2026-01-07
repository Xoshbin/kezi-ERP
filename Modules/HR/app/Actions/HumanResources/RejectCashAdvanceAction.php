<?php

namespace Modules\HR\Actions\HumanResources;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\HR\Enums\CashAdvanceStatus;
use Modules\HR\Models\CashAdvance;

class RejectCashAdvanceAction
{
    public function execute(CashAdvance $cashAdvance, string $reason, User $user): void
    {
        DB::transaction(function () use ($cashAdvance, $reason) {
            if ($cashAdvance->status !== CashAdvanceStatus::PendingApproval) {
                throw new \InvalidArgumentException('Only pending cash advances can be rejected.');
            }

            $cashAdvance->update([
                'status' => CashAdvanceStatus::Rejected,
                'notes' => ($cashAdvance->notes ? $cashAdvance->notes."\n\n" : '')."Rejection reason: {$reason}",
            ]);
        });
    }
}
