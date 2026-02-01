<?php

namespace Jmeryar\HR\Actions\HumanResources;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Jmeryar\HR\Enums\CashAdvanceStatus;
use Jmeryar\HR\Models\CashAdvance;

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
        });
    }
}
