<?php

namespace App\Filament\Clusters\Accounting\Resources\BankStatements\Pages;

use App\Actions\Accounting\CreateBankStatementAction;
use App\DataTransferObjects\Accounting\CreateBankStatementDTO;
use App\DataTransferObjects\Accounting\CreateBankStatementLineDTO;
use App\Filament\Clusters\Accounting\Resources\BankStatements\BankStatementResource;
use App\Models\Currency;
use Brick\Money\Money;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateBankStatement extends CreateRecord
{
    protected static string $resource = BankStatementResource::class;

    public function getTitle(): string
    {
        return __('bank_statement.create_bank_statement');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currency = Currency::find($data['currency_id']);
        $lineDTOs = [];
        foreach ($data['bankStatementLines'] as $line) {
            $foreignCurrency = null;
            $amountInForeignCurrency = null;

            if (! empty($line['foreign_currency_id']) && ! empty($line['amount_in_foreign_currency'])) {
                $foreignCurrency = Currency::find($line['foreign_currency_id']);
                $amountInForeignCurrency = Money::of($line['amount_in_foreign_currency'], $foreignCurrency->code);
            }

            $lineDTOs[] = new CreateBankStatementLineDTO(
                date: $line['date'],
                description: $line['description'],
                amount: Money::of($line['amount'], $currency->code),
                partner_id: $line['partner_id'],
                foreign_currency_id: $line['foreign_currency_id'] ?? null,
                amount_in_foreign_currency: $amountInForeignCurrency
            );
        }
        $data['bankStatementLines'] = $lineDTOs;
        $data['created_by_user_id'] = Auth::id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $bankStatementDTO = new CreateBankStatementDTO(
            company_id: (int) (Filament::getTenant()->id ?? 0),
            currency_id: $data['currency_id'],
            journal_id: $data['journal_id'],
            reference: $data['reference'],
            date: $data['date'],
            starting_balance: Money::of($data['starting_balance'], Currency::find($data['currency_id'])->code),
            ending_balance: Money::of($data['ending_balance'], Currency::find($data['currency_id'])->code),
            lines: $data['bankStatementLines']
        );

        return app(CreateBankStatementAction::class)->execute($bankStatementDTO);
    }
}
