<?php

namespace Jmeryar\HR\Actions\HumanResources;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Jmeryar\HR\Enums\CashAdvanceStatus;
use Jmeryar\HR\Models\CashAdvance;

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
