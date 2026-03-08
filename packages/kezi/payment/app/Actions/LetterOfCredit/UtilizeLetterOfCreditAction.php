<?php

namespace Kezi\Payment\Actions\LetterOfCredit;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Kezi\Payment\DataTransferObjects\LetterOfCredit\UtilizeLCDTO;
use Kezi\Payment\Models\LCUtilization;
use Kezi\Payment\Models\LetterOfCredit;

class UtilizeLetterOfCreditAction
{
    public function execute(LetterOfCredit $lc, UtilizeLCDTO $dto, User $user): LCUtilization
    {
        return DB::transaction(function () use ($lc, $dto) {
            // Validate LC can be utilized
            if (! $lc->canBeUtilized()) {
                throw new \RuntimeException(__('payment::exceptions.lc.invalid_status_or_expired'));
            }

            // Validate amount doesn't exceed balance
            if ($dto->utilized_amount->isGreaterThan($lc->balance)) {
                throw new \RuntimeException(__('payment::exceptions.lc.utilization_exceeds_balance'));
            }

            // Create utilization record
            $utilization = LCUtilization::create([
                'company_id' => $lc->company_id,
                'letter_of_credit_id' => $lc->id,
                'vendor_bill_id' => $dto->vendor_bill_id,
                'utilized_amount' => $dto->utilized_amount,
                'utilized_amount_company_currency' => $dto->utilized_amount_company_currency,
                'utilization_date' => $dto->utilization_date,
            ]);

            // Recalculate LC balance and update status
            $lc->recalculateBalance();

            return $utilization;
        });
    }
}
