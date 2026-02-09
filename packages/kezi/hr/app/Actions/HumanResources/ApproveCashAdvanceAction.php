<?php

namespace Kezi\HR\Actions\HumanResources;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Kezi\Foundation\Models\AuditLog;
use Kezi\HR\Enums\CashAdvanceStatus;
use Kezi\HR\Models\CashAdvance;

class ApproveCashAdvanceAction
{
    public function execute(CashAdvance $cashAdvance, Money $approvedAmount, User $user): void
    {
        DB::transaction(function () use ($cashAdvance, $approvedAmount, $user) {
            if ($cashAdvance->status !== CashAdvanceStatus::PendingApproval) {
                throw new \InvalidArgumentException('Only pending cash advances can be approved.');
            }

            if ($approvedAmount->isGreaterThan($cashAdvance->requested_amount)) {
                throw new \InvalidArgumentException('Approved amount cannot exceed requested amount.');
            }
            $cashAdvance->update([
                'approved_amount' => $approvedAmount,
                'status' => CashAdvanceStatus::Approved,
                'approved_at' => now(),
                'approved_by_user_id' => $user->id,
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'company_id' => $cashAdvance->company_id,
                'event_type' => 'cash_advance_approved',
                'auditable_type' => get_class($cashAdvance),
                'auditable_id' => $cashAdvance->getKey(),
                'description' => "Approved amount: {$approvedAmount->formatTo('en_US')}",
                'ip_address' => request()->ip(),
            ]);
        });
    }
}
