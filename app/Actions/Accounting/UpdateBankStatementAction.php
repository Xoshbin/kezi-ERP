<?php

namespace App\Actions\Accounting;

use Brick\Money\Money;
use App\Models\Currency;
use App\Models\BankStatement;
use Illuminate\Support\Facades\DB;
use App\DataTransferObjects\Accounting\UpdateBankStatementDTO;

class UpdateBankStatementAction
{
    public function execute(UpdateBankStatementDTO $dto): BankStatement
    {
        return DB::transaction(function () use ($dto) {
            $bankStatement = $dto->bankStatement;

            // 1. Update the parent statement's details
            $bankStatement->update([
                'currency_id' => $dto->currency_id,
                'journal_id' => $dto->journal_id,
                'reference' => $dto->reference,
                'date' => $dto->date,
                'starting_balance' => $dto->starting_balance,
                'ending_balance' => $dto->ending_balance,
            ]);

            $existingLineIds = $bankStatement->bankStatementLines()->pluck('id')->toArray();
            $incomingLineIds = [];

            // 2. Update existing lines and create new ones
            $bankStatement->bankStatementLines()->delete();


            // 3. Delete lines that were removed from the form
            if (!empty($dto->lines)) {
                $currencyCode = Currency::find($dto->currency_id)->code;
                $linesToCreate = [];

                foreach ($dto->lines as $lineDto) {
                    $linesToCreate[] = [
                        'company_id' => $bankStatement->company_id,
                        'date' => $lineDto->date,
                        'description' => $lineDto->description,
                        'partner_id' => $lineDto->partner_id,
                        // Create the Money object here, so the Cast receives a complete object.
                        'amount' => Money::of($lineDto->amount, $currencyCode),
                    ];
                }

                $bankStatement->bankStatementLines()->createMany($linesToCreate);
            }

            return $bankStatement;
        });
    }
}
