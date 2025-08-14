<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\Asset;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\CurrencyConverterService;
use RuntimeException;

class CreateJournalEntryForAssetAcquisitionAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction,
        private readonly CurrencyConverterService $currencyConverter
    ) {}

    public function execute(Asset $asset, User $user): JournalEntry
    {
        // Load necessary relationships for multi-currency handling
        $asset->load('company.currency', 'currency');
        $company = $asset->company;

        $payableAccountId = $company->default_accounts_payable_id;
        if (!$payableAccountId) {
            throw new RuntimeException('Default Accounts Payable is not configured for this company.');
        }

        // Use CurrencyConverterService for all currency conversion logic
        $conversion = $this->currencyConverter->convertToCompanyBaseCurrency(
            $asset->purchase_value,
            $asset->currency,
            $company
        );

        $zeroAmountInBase = $conversion->createZeroInTargetCurrency();

        $lines = [
            new CreateJournalEntryLineDTO(
                account_id: $asset->asset_account_id,
                debit: $conversion->convertedAmount,
                credit: $zeroAmountInBase,
                description: 'Asset Acquisition: ' . $asset->name,
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $conversion->originalAmount,
                original_currency_id: $conversion->originalCurrency->id,
                exchange_rate_at_transaction: $conversion->exchangeRate,
            ),
            new CreateJournalEntryLineDTO(
                account_id: $payableAccountId,
                credit: $conversion->convertedAmount,
                debit: $zeroAmountInBase,
                description: 'Acquisition of Asset: ' . $asset->name,
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $conversion->originalAmount,
                original_currency_id: $conversion->originalCurrency->id,
                exchange_rate_at_transaction: $conversion->exchangeRate,
            ),
        ];

        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $asset->company_id,
            journal_id: $company->default_depreciation_journal_id,
            currency_id: $conversion->targetCurrency->id, // Journal entry is always in company base currency
            entry_date: $asset->purchase_date->toDateString(),
            reference: 'ASSET/' . $asset->id,
            description: 'Acquisition of Asset: ' . $asset->name,
            created_by_user_id: $user->id,
            is_posted: true,
            lines: $lines,
            source_type: Asset::class,
            source_id: $asset->id
        );

        return $this->createJournalEntryAction->execute($journalEntryDTO);
    }
}
