<?php

namespace Modules\Payment\Actions\LetterOfCredit;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Payment\DataTransferObjects\LetterOfCredit\UtilizeLCDTO;
use Modules\Payment\Models\LCUtilization;
use Modules\Payment\Models\LetterOfCredit;

class UtilizeLetterOfCreditAction
{
    public function execute(LetterOfCredit $lc, UtilizeLCDTO $dto, User $user): LCUtilization
    {
        return DB::transaction(function () use ($lc, $dto) {
            // Validate LC can be utilized
            if (! $lc->canBeUtilized()) {
                throw new \RuntimeException('LC cannot be utilized in current status or is expired');
            }

            // Validate amount doesn't exceed balance
            if ($dto->utilized_amount->isGreaterThan($lc->balance)) {
                throw new \RuntimeException('Utilization amount exceeds LC balance');
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
