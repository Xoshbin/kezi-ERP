<?php

namespace App\Filament\Resources\JournalEntryResource\Pages;

use App\Actions\Accounting\CreateJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Filament\Resources\JournalEntryResource;
use App\Models\Company;
use App\Models\Journal;
use App\Models\LockDate;
use Brick\Money\Money;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

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
            $baseCurrency = $company->currency;

            if ($selectedCurrency && $baseCurrency) {
                // Determine exchange rate for conversion
                $exchangeRate = ($baseCurrency->id === $selectedCurrency->id) ? 1.0 : $selectedCurrency->exchange_rate;

                foreach ($data['lines'] as $line) {
                    // Create original amounts in selected currency
                    $originalDebit = Money::of($line['debit'] ?? 0, $selectedCurrency->code);
                    $originalCredit = Money::of($line['credit'] ?? 0, $selectedCurrency->code);

                    // Convert amounts to company base currency
                    $convertedDebit = Money::of(
                        $originalDebit->getAmount()->multipliedBy($exchangeRate),
                        $baseCurrency->code,
                        null,
                        \Brick\Math\RoundingMode::HALF_UP
                    );
                    $convertedCredit = Money::of(
                        $originalCredit->getAmount()->multipliedBy($exchangeRate),
                        $baseCurrency->code,
                        null,
                        \Brick\Math\RoundingMode::HALF_UP
                    );

                    // Determine which original amount to store (the non-zero one)
                    $originalAmount = $originalDebit->isPositive() ? $originalDebit : $originalCredit;

                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $line['account_id'],
                        debit: $convertedDebit,
                        credit: $convertedCredit,
                        description: $line['description'],
                        partner_id: $line['partner_id'],
                        analytic_account_id: $line['analytic_account_id'],
                        original_currency_amount: $originalAmount,
                        original_currency_id: $selectedCurrency->id,
                        exchange_rate_at_transaction: $exchangeRate
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
