<?php

namespace App\Filament\Resources\BankStatements\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\BankStatements\BankStatementResource;
use App\Actions\Accounting\CreateBankStatementAction;
use App\DataTransferObjects\Accounting\CreateBankStatementDTO;
use App\DataTransferObjects\Accounting\CreateBankStatementLineDTO;
use App\Models\Currency;
use Brick\Money\Money;
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
            $lineDTOs[] = new CreateBankStatementLineDTO(
                date: $line['date'],
                description: $line['description'],
                amount: Money::of($line['amount'], $currency->code),
                partner_id: $line['partner_id']
            );
        }
        $data['bankStatementLines'] = $lineDTOs;
        $data['created_by_user_id'] = Auth::id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $bankStatementDTO = new CreateBankStatementDTO(
            company_id: $data['company_id'],
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
