<?php

namespace Kezi\Pos\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Kezi\Pos\Enums\PosReturnStatus;
use Kezi\Pos\Models\PosReturn;

class ApprovePosReturnAction
{
    public function execute(PosReturn $return, User $approver): PosReturn
    {
        return DB::transaction(function () use ($return, $approver) {
            if (! $return->canBeApproved()) {
                throw new \InvalidArgumentException('Return cannot be approved in current status');
            }

            $return->update([
                'status' => PosReturnStatus::Approved,
                'approved_by_user_id' => $approver->id,
                'approved_at' => now(),
            ]);

            return $return->fresh();
        });
    }
}
