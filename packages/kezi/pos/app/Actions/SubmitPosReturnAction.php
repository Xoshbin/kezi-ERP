<?php

namespace Kezi\Pos\Actions;

use Illuminate\Support\Facades\DB;
use Kezi\Pos\Enums\PosReturnStatus;
use Kezi\Pos\Models\PosReturn;

class SubmitPosReturnAction
{
    public function execute(PosReturn $return): PosReturn
    {
        return DB::transaction(function () use ($return) {
            if ($return->status !== PosReturnStatus::Draft) {
                throw new \InvalidArgumentException(__('pos::exceptions.pos_return.only_draft_can_be_submitted'));
            }

            // Determine next status based on approval requirement
            $newStatus = $return->requiresApproval()
                ? PosReturnStatus::PendingApproval
                : PosReturnStatus::Approved;

            $return->update([
                'status' => $newStatus,
            ]);

            return $return->fresh();
        });
    }
}
