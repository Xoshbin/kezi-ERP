<?php

namespace Kezi\Pos\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Kezi\Pos\Enums\PosReturnStatus;
use Kezi\Pos\Models\PosReturn;

class RejectPosReturnAction
{
    public function execute(PosReturn $return, User $rejector, string $reason): PosReturn
    {
        return DB::transaction(function () use ($return, $rejector, $reason) {
            if (! $return->canBeApproved()) {
                throw new \InvalidArgumentException('Return cannot be rejected in current status');
            }

            $return->update([
                'status' => PosReturnStatus::Rejected,
                'approved_by_user_id' => $rejector->id,
                'approved_at' => now(),
                'return_notes' => ($return->return_notes ?? '')."\n\nRejection Reason: ".$reason,
            ]);

            return $return->fresh();
        });
    }
}
