<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateBankStatementDTO;
use App\Models\BankStatement;
use App\Models\Currency; // <-- Import the Currency model
use Brick\Money\Money; // <-- Import the Money object
use Illuminate\Support\Facades\DB;

class CreateBankStatementAction
{
    public function execute(CreateBankStatementDTO $dto): BankStatement
    {
        return DB::transaction(function () use ($dto) {
            // 1. Create the parent BankStatement first.
            $bankStatement = BankStatement::create([
                'company_id' => $dto->company_id,
                'currency_id' => $dto->currency_id,
                'journal_id' => $dto->journal_id,
                'reference' => $dto->reference,
                'date' => $dto->date,
                'starting_balance' => $dto->starting_balance,
                'ending_balance' => $dto->ending_balance,
            ]);

            // 2. Find the currency code to create Money objects.
            $currencyCode = Currency::find($dto->currency_id)->code;

            // 3. Prepare the lines, creating Money objects directly.
            $linesToCreate = [];
            foreach ($dto->lines as $lineDto) {
                $linesToCreate[] = [
                    'company_id' => $bankStatement->company_id,
                    'date' => $lineDto->date,
                    'description' => $lineDto->description,
                    'partner_id' => $lineDto->partner_id,

                    // THE FIX: Convert the string from the DTO into a Money object here.
                    'amount' => Money::of($lineDto->amount, $currencyCode),
                ];
            }

            // 4. Create all lines at once. The MoneyCast will now receive a valid Money object.
            if (!empty($linesToCreate)) {
                $bankStatement->bankStatementLines()->createMany($linesToCreate);
            }

            return $bankStatement;
        });
    }
}
