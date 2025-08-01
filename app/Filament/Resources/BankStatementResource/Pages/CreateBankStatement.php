<?php

namespace App\Filament\Resources\BankStatementResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\BankStatementResource;
use App\Actions\Accounting\CreateBankStatementAction;
use App\DataTransferObjects\Accounting\CreateBankStatementDTO;
use App\DataTransferObjects\Accounting\CreateBankStatementLineDTO;

class CreateBankStatement extends CreateRecord
{
    protected static string $resource = BankStatementResource::class;

    public function getTitle(): string
    {
        return __('bank_statement.create_bank_statement');
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Prepare Line DTOs
        $lineDTOs = [];
        if (isset($data['bankStatementLines'])) {
            foreach ($data['bankStatementLines'] as $line) {
                $lineDTOs[] = new CreateBankStatementLineDTO(
                    date: $line['date'],
                    description: $line['description'],
                    amount: $line['amount'],
                    partner_id: $line['partner_id']
                );
            }
        }

        // Prepare Parent DTO
        $dto = new CreateBankStatementDTO(
            company_id: $data['company_id'],
            currency_id: $data['currency_id'],
            journal_id: $data['journal_id'],
            reference: $data['reference'],
            date: $data['date'],
            starting_balance: $data['starting_balance'],
            ending_balance: $data['ending_balance'],
            lines: $lineDTOs
        );

        // Execute the Action
        return (new CreateBankStatementAction())->execute($dto);
    }
}
