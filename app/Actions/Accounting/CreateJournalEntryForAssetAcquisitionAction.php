<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\Asset;
use App\Models\JournalEntry;
use App\Models\User;
use Brick\Money\Money;
use RuntimeException;

class CreateJournalEntryForAssetAcquisitionAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction
    ) {}

    public function execute(Asset $asset, User $user): JournalEntry
    {
        // Load necessary relationships for multi-currency handling
        $asset->load('company.currency', 'currency');
        $company = $asset->company;
        $baseCurrency = $company->currency;
        $assetCurrency = $asset->currency;

        $payableAccountId = $company->default_accounts_payable_id;
        if (!$payableAccountId) {
            throw new RuntimeException('Default Accounts Payable is not configured for this company.');
        }

        // Determine the exchange rate. If it's the same currency, the rate is 1.
        $exchangeRate = ($baseCurrency->id === $assetCurrency->id) ? 1.0 : $assetCurrency->exchange_rate;

        // Convert asset purchase value to company base currency
        $purchaseValueInBase = Money::of(
            $asset->purchase_value->getAmount()->multipliedBy($exchangeRate),
            $baseCurrency->code,
            null,
            \Brick\Math\RoundingMode::HALF_UP
        );

        $zeroAmountInBase = Money::zero($baseCurrency->code);

        $lines = [
            new CreateJournalEntryLineDTO(
                account_id: $asset->asset_account_id,
                debit: $purchaseValueInBase,
                credit: $zeroAmountInBase,
                description: 'Asset Acquisition: ' . $asset->name,
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $asset->purchase_value, // Original Money object
                original_currency_id: $asset->currency_id, // Original currency ID
                exchange_rate_at_transaction: $exchangeRate,
            ),
            new CreateJournalEntryLineDTO(
                account_id: $payableAccountId,
                credit: $purchaseValueInBase,
                debit: $zeroAmountInBase,
                description: 'Acquisition of Asset: ' . $asset->name,
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $asset->purchase_value, // Original Money object
                original_currency_id: $asset->currency_id, // Original currency ID
                exchange_rate_at_transaction: $exchangeRate,
            ),
        ];

        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $asset->company_id,
            journal_id: $company->default_depreciation_journal_id,
            currency_id: $baseCurrency->id, // Journal entry is always in company base currency
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
