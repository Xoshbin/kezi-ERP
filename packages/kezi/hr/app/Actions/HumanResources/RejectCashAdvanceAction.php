<?php

namespace Kezi\HR\Actions\HumanResources;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Kezi\Foundation\Models\AuditLog;
use Kezi\HR\Enums\CashAdvanceStatus;
use Kezi\HR\Models\CashAdvance;

class RejectCashAdvanceAction
{
    public function execute(CashAdvance $cashAdvance, string $reason, User $user): void
    {
        DB::transaction(function () use ($cashAdvance, $reason, $user) {
            if ($cashAdvance->status !== CashAdvanceStatus::PendingApproval) {
                throw new \InvalidArgumentException('Only pending cash advances can be rejected.');
            }

            $cashAdvance->update([
                'status' => CashAdvanceStatus::Rejected,
                'notes' => ($cashAdvance->notes ? $cashAdvance->notes."\n\n" : '')."Rejection reason: {$reason}",
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'company_id' => $cashAdvance->company_id,
                'event_type' => 'cash_advance_rejected',
                'auditable_type' => get_class($cashAdvance),
                'auditable_id' => $cashAdvance->getKey(),
                'description' => $reason,
                'ip_address' => request()->ip(),
            ]);
        });
    }
}
