<?php

namespace Jmeryar\Payment\Actions\PettyCash;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Jmeryar\Payment\Enums\PettyCash\PettyCashFundStatus;
use Jmeryar\Payment\Models\PettyCash\PettyCashFund;

class ClosePettyCashFundAction
{
    public function execute(PettyCashFund $fund, User $user): void
    {
        DB::transaction(function () use ($fund) {
            // Validate that the fund has zero balance before closing
            if (! $fund->current_balance->isZero()) {
                throw new \InvalidArgumentException('Cannot close petty cash fund with non-zero balance.');
            }

            $fund->update([
                'status' => PettyCashFundStatus::Closed,
            ]);
        });
    }
}
