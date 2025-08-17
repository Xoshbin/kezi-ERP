<?php

namespace App\Filament\Resources\BankStatements\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\BankStatements\BankStatementResource;
use App\Actions\Accounting\UpdateBankStatementAction;
use App\DataTransferObjects\Accounting\UpdateBankStatementDTO;
use App\DataTransferObjects\Accounting\UpdateBankStatementLineDTO;
use App\Models\Currency;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;

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
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('bankStatementLines', 'currency');
        $linesData = $this->record->bankStatementLines->map(function ($line) {
            return [
                'date' => $line->date->format('Y-m-d'),
                'description' => $line->description,
                'amount' => $line->amount,
                'partner_id' => $line->partner_id,
                'foreign_currency_id' => $line->foreign_currency_id,
                'amount_in_foreign_currency' => $line->amount_in_foreign_currency,
            ];
        })->toArray();
        $data['bankStatementLines'] = $linesData;
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $lineDTOs = [];
        foreach ($data['bankStatementLines'] as $line) {
            $foreignCurrency = null;
            $amountInForeignCurrency = null;

            if (!empty($line['foreign_currency_id']) && !empty($line['amount_in_foreign_currency'])) {
                $foreignCurrency = Currency::find($line['foreign_currency_id']);
                $amountInForeignCurrency = Money::of($line['amount_in_foreign_currency'], $foreignCurrency->code);
            }

            $lineDTOs[] = new UpdateBankStatementLineDTO(
                id: $line['id'] ?? null,
                date: $line['date'],
                description: $line['description'],
                amount: Money::of($line['amount'], $record->currency->code),
                partner_id: $line['partner_id'],
                foreign_currency_id: $line['foreign_currency_id'] ?? null,
                amount_in_foreign_currency: $amountInForeignCurrency
            );
        }

        $dto = new UpdateBankStatementDTO(
            bankStatement: $record,
            currency_id: $data['currency_id'],
            journal_id: $data['journal_id'],
            reference: $data['reference'],
            date: $data['date'],
            starting_balance: Money::of($data['starting_balance'], $record->currency->code),
            ending_balance: Money::of($data['ending_balance'], $record->currency->code),
            lines: $lineDTOs
        );

        return app(UpdateBankStatementAction::class)->execute($dto);
    }
}
