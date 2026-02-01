<?php

namespace Jmeryar\Accounting\Actions\Accounting;

use Illuminate\Support\Facades\DB;
use Jmeryar\Accounting\DataTransferObjects\Accounting\CreateBankStatementDTO;
use Jmeryar\Accounting\Models\BankStatement;

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

            // 2. Prepare the lines, using Money objects directly from DTO.
            $linesToCreate = [];
            foreach ($dto->lines as $lineDto) {
                $linesToCreate[] = [
                    'company_id' => $bankStatement->company_id,
                    'date' => $lineDto->date,
                    'description' => $lineDto->description,
                    'partner_id' => $lineDto->partner_id,
                    'amount' => $lineDto->amount,
                    'foreign_currency_id' => $lineDto->foreign_currency_id,
                    'amount_in_foreign_currency' => $lineDto->amount_in_foreign_currency,
                ];
            }

            // 3. Create all lines at once. The MoneyCast will receive valid Money objects.
            if (! empty($linesToCreate)) {
                $bankStatement->bankStatementLines()->createMany($linesToCreate);
            }

            return $bankStatement;
        });
    }
}
