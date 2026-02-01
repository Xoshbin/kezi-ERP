<?php

namespace Jmeryar\Payment\Actions\LetterOfCredit;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Jmeryar\Payment\DataTransferObjects\LetterOfCredit\CreateLCChargeDTO;
use Jmeryar\Payment\Models\LCCharge;

class CreateLCChargeAction
{
    public function __construct(
        private readonly CreateJournalEntryForLCChargeAction $createJournalEntryForLCChargeAction
    ) {}

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

            $journalEntry = $this->createJournalEntryForLCChargeAction->execute($charge);

            $charge->update([
                'journal_entry_id' => $journalEntry->id,
            ]);

            return $charge;
        });
    }
}
