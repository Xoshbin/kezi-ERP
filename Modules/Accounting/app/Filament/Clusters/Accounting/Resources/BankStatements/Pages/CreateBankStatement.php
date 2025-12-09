<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\BankStatements\Pages;

use Brick\Money\Money;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Modules\Accounting\Actions\Accounting\CreateBankStatementAction;
use Modules\Accounting\DataTransferObjects\Accounting\CreateBankStatementDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateBankStatementLineDTO;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\BankStatements\BankStatementResource;
use Modules\Foundation\Filament\Actions\DocsAction;
use Modules\Foundation\Models\Currency;

class CreateBankStatement extends CreateRecord
{
    protected static string $resource = BankStatementResource::class;

    public function getTitle(): string
    {
        return __('accounting::bank_statement.create_bank_statement');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currency = Currency::findOrFail($data['currency_id']);
        // Ensure we have a single Currency model, not a collection
        if ($currency instanceof Collection) {
            $currency = $currency->first();
            if (! $currency) {
                throw new InvalidArgumentException('Currency not found');
            }
        }
        $lineDTOs = [];
        foreach ($data['bankStatementLines'] as $line) {
            $foreignCurrency = null;
            $amountInForeignCurrency = null;

            if (! empty($line['foreign_currency_id']) && ! empty($line['amount_in_foreign_currency'])) {
                $foreignCurrency = Currency::findOrFail($line['foreign_currency_id']);
                // Ensure we have a single Currency model, not a collection
                if ($foreignCurrency instanceof Collection) {
                    $foreignCurrency = $foreignCurrency->first();
                    if (! $foreignCurrency) {
                        throw new InvalidArgumentException('Foreign currency not found');
                    }
                }
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
        $currency = Currency::findOrFail($data['currency_id']);
        // Ensure we have a single Currency model, not a collection
        if ($currency instanceof Collection) {
            $currency = $currency->first();
            if (! $currency) {
                throw new InvalidArgumentException('Currency not found');
            }
        }

        $bankStatementDTO = new CreateBankStatementDTO(
            company_id: (int) (Filament::getTenant()->id ?? 0),
            currency_id: $data['currency_id'],
            journal_id: $data['journal_id'],
            reference: $data['reference'],
            date: $data['date'],
            starting_balance: Money::of($data['starting_balance'], $currency->code),
            ending_balance: Money::of($data['ending_balance'], $currency->code),
            lines: $data['bankStatementLines']
        );

        return app(CreateBankStatementAction::class)->execute($bankStatementDTO);
    }

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('bank-statements'),
        ];
    }
}
