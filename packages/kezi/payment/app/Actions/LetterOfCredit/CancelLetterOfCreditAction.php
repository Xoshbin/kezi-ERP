<?php

namespace Kezi\Payment\Actions\LetterOfCredit;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Kezi\Payment\Enums\LetterOfCredit\LCStatus;
use Kezi\Payment\Models\LetterOfCredit;

class CancelLetterOfCreditAction
{
    public function execute(LetterOfCredit $lc, User $user): void
    {
        DB::transaction(function () use ($lc) {
            // Can only cancel draft or issued LCs that haven't been utilized
            if (! in_array($lc->status, [LCStatus::Draft, LCStatus::Issued])) {
                throw new \RuntimeException(__('payment::exceptions.lc.cancel_condition'));
            }

            if ($lc->utilized_amount->isPositive()) {
                throw new \RuntimeException(__('payment::exceptions.lc.cannot_cancel_utilized'));
            }

            $lc->update([
                'status' => LCStatus::Cancelled,
            ]);
        });
    }
}
