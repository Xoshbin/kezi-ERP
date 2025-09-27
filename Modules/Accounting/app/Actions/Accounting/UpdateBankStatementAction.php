<?php

namespace Modules\Accounting\Actions\Accounting;

use App\DataTransferObjects\Accounting\UpdateBankStatementDTO;
use App\Models\BankStatement;
use Illuminate\Support\Facades\DB;

class UpdateBankStatementAction
{
    public function execute(UpdateBankStatementDTO $dto): \Modules\Accounting\Models\BankStatement
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

            // 2. Delete existing lines and recreate them
            $bankStatement->bankStatementLines()->delete();

            // 3. Create new lines from DTO
            if (! empty($dto->lines)) {
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

                $bankStatement->bankStatementLines()->createMany($linesToCreate);
            }

            return $bankStatement;
        });
    }
}
