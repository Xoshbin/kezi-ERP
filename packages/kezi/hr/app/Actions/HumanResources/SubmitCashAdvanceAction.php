<?php

namespace Kezi\HR\Actions\HumanResources;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Kezi\HR\Enums\CashAdvanceStatus;
use Kezi\HR\Models\CashAdvance;

class SubmitCashAdvanceAction
{
    public function execute(CashAdvance $cashAdvance, User $user): void
    {
        DB::transaction(function () use ($cashAdvance) {
            if ($cashAdvance->status !== CashAdvanceStatus::Draft) {
                throw new \InvalidArgumentException('Only draft cash advances can be submitted.');
            }

            $cashAdvance->update([
                'status' => CashAdvanceStatus::PendingApproval,
                'requested_at' => now(),
            ]);
        });
    }
}
