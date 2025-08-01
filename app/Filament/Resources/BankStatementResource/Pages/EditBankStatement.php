<?php

namespace App\Filament\Resources\BankStatementResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\BankStatementResource;
use App\Actions\Accounting\UpdateBankStatementAction;
use App\DataTransferObjects\Accounting\UpdateBankStatementDTO;
use App\DataTransferObjects\Accounting\UpdateBankStatementLineDTO;

class EditBankStatement extends EditRecord
{
    protected static string $resource = BankStatementResource::class;

    public function getTitle(): string
    {
        return __('bank_statement.edit_bank_statement');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // 1. Load the relationship and format it for the Repeater.
        // The key 'bankStatementLines' must match the ->name() of your Repeater component.
        $data['bankStatementLines'] = $this->record->bankStatementLines->map(function ($line) {
            // Convert Money objects to plain strings for the form fields.
            return [
                'date' => $line->date->format('Y-m-d'),
                'description' => $line->description,
                'amount' => (string) $line->amount->getAmount(),
                'partner_id' => $line->partner_id,
            ];
        })->all();

        // 2. Convert the parent's Money objects to plain strings for the TextInputs.
        $data['starting_balance'] = (string) $this->record->starting_balance->getAmount();
        $data['ending_balance'] = (string) $this->record->ending_balance->getAmount();

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $lineDTOs = [];
        if (isset($data['bankStatementLines'])) {
            foreach ($data['bankStatementLines'] as $line) {
                $lineDTOs[] = new UpdateBankStatementLineDTO(
                    id: $line['id'] ?? null,
                    date: $line['date'],
                    description: $line['description'],
                    amount: $line['amount'],
                    partner_id: $line['partner_id']
                );
            }
        }

        $dto = new UpdateBankStatementDTO(
            bankStatement: $record,
            currency_id: $data['currency_id'],
            journal_id: $data['journal_id'],
            reference: $data['reference'],
            date: $data['date'],
            starting_balance: $data['starting_balance'],
            ending_balance: $data['ending_balance'],
            lines: $lineDTOs
        );

        return (new UpdateBankStatementAction())->execute($dto);
    }
}
