<?php

namespace Modules\Payment\Actions\LetterOfCredit;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Payment\DataTransferObjects\LetterOfCredit\CreateLCChargeDTO;
use Modules\Payment\Models\LCCharge;

class CreateLCChargeAction
{
    public function execute(CreateLCChargeDTO $dto, User $user): LCCharge
    {
        return DB::transaction(function () use ($dto) {
            $charge = LCCharge::create([
                'company_id' => $dto->company_id,
                'letter_of_credit_id' => $dto->letter_of_credit_id,
                'account_id' => $dto->account_id,
                'currency_id' => $dto->currency_id,
                'charge_type' => $dto->charge_type,
                'amount' => $dto->amount,
                'amount_company_currency' => $dto->amount_company_currency,
                'charge_date' => $dto->charge_date,
                'description' => $dto->description,
            ]);

            // TODO: Create journal entry for the charge via CreateJournalEntryForLCChargeAction

            return $charge;
        });
    }
}
