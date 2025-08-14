<?php

namespace App\Filament\Resources\JournalEntryResource\Pages;

use App\Actions\Accounting\CreateJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Filament\Resources\JournalEntryResource;
use App\Services\CurrencyConverterService;
use Brick\Money\Money;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $lineDTOs = [];
        if (isset($data['lines']) && is_array($data['lines'])) {
            // Get the selected currency and company base currency
            $selectedCurrency = \App\Models\Currency::find($data['currency_id']);
            $company = \Filament\Facades\Filament::getTenant();
            $currencyConverter = app(CurrencyConverterService::class);

            if ($selectedCurrency && $company) {
                foreach ($data['lines'] as $line) {
                    // Handle both Money objects and numeric values from form
                    $debitValue = $line['debit'] ?? 0;
                    $creditValue = $line['credit'] ?? 0;

                    // If the values are already Money objects (from MoneyInput), extract the amount
                    if ($debitValue instanceof Money) {
                        $debitValue = $debitValue->getAmount()->toFloat();
                    }
                    if ($creditValue instanceof Money) {
                        $creditValue = $creditValue->getAmount()->toFloat();
                    }

                    // Create original amounts in selected currency
                    $originalDebit = Money::of($debitValue, $selectedCurrency->code);
                    $originalCredit = Money::of($creditValue, $selectedCurrency->code);

                    // Use CurrencyConverterService for conversion
                    $debitConversion = $currencyConverter->convertToCompanyBaseCurrency(
                        $originalDebit,
                        $selectedCurrency,
                        $company
                    );
                    $creditConversion = $currencyConverter->convertToCompanyBaseCurrency(
                        $originalCredit,
                        $selectedCurrency,
                        $company
                    );

                    // Determine which original amount to store (the non-zero one)
                    $originalAmount = $originalDebit->isPositive() ? $originalDebit : $originalCredit;
                    $conversion = $originalDebit->isPositive() ? $debitConversion : $creditConversion;

                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $line['account_id'],
                        debit: $debitConversion->convertedAmount,
                        credit: $creditConversion->convertedAmount,
                        description: $line['description'],
                        partner_id: $line['partner_id'],
                        analytic_account_id: $line['analytic_account_id'],
                        original_currency_amount: $originalAmount,
                        original_currency_id: $conversion->originalCurrency->id,
                        exchange_rate_at_transaction: $conversion->exchangeRate
                    );
                }
            }
        }
        $data['lines'] = $lineDTOs;
        $data['created_by_user_id'] = \Illuminate\Support\Facades\Auth::id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Always use company base currency for journal entries
        $company = \Filament\Facades\Filament::getTenant();

        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $company->id,
            journal_id: $data['journal_id'],
            currency_id: $company->currency_id, // Always use company base currency
            entry_date: $data['entry_date'],
            reference: $data['reference'],
            description: $data['description'],
            created_by_user_id: $data['created_by_user_id'],
            is_posted: false,
            lines: $data['lines']
        );

        return app(CreateJournalEntryAction::class)->execute($journalEntryDTO);
    }
}
