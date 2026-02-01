<?php

namespace Jmeryar\Payment\Actions\LetterOfCredit;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Jmeryar\Payment\Enums\LetterOfCredit\LCStatus;
use Jmeryar\Payment\Models\LetterOfCredit;

class CancelLetterOfCreditAction
{
    public function execute(LetterOfCredit $lc, User $user): void
    {
        DB::transaction(function () use ($lc) {
            // Can only cancel draft or issued LCs that haven't been utilized
            if (! in_array($lc->status, [LCStatus::Draft, LCStatus::Issued])) {
                throw new \RuntimeException('LC can only be cancelled if it is draft or issued without utilization');
            }

            if ($lc->utilized_amount->isPositive()) {
                throw new \RuntimeException('Cannot cancel LC that has been utilized');
            }

            $lc->update([
                'status' => LCStatus::Cancelled,
            ]);
        });
    }
}
