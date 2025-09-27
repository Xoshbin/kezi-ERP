<?php

namespace Modules\Accounting\Actions\Accounting;

use App\Models\User;
use Brick\Money\Money;
use InvalidArgumentException;
use Modules\Accounting\Models\Asset;
use Modules\Foundation\Models\Currency;
use RuntimeException;

class CreateJournalEntryForAssetAcquisitionAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction
    ) {}

    public function execute(Asset $asset, User $user): JournalEntry
    {
        $company = $asset->company;
        /** @var Currency $assetCurrency */
        $assetCurrency = $asset->currency;

        $payableAccountId = $company->default_accounts_payable_id;
        if (! $payableAccountId) {
            throw new RuntimeException('Default Accounts Payable is not configured for this company.');
        }

        $purchaseValue = $asset->purchase_value;

        $lines = [
            new CreateJournalEntryLineDTO(
                account_id: $asset->asset_account_id,
                debit: $purchaseValue,
                credit: Money::zero($assetCurrency->code),
                description: 'Asset Acquisition: '.$asset->name,
                partner_id: null,
                analytic_account_id: null,
            ),
            new CreateJournalEntryLineDTO(
                account_id: $payableAccountId,
                credit: $purchaseValue,
                debit: Money::zero($assetCurrency->code),
                description: 'Acquisition of Asset: '.$asset->name,
                partner_id: null,
                analytic_account_id: null,
            ),
        ];

        if (! $company->default_depreciation_journal_id) {
            throw new InvalidArgumentException('Company default depreciation journal is not configured');
        }

        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $asset->company_id,
            journal_id: $company->default_depreciation_journal_id,
            currency_id: $asset->currency_id,
            entry_date: $asset->purchase_date->toDateString(),
            reference: 'ASSET/'.$asset->id,
            description: 'Acquisition of Asset: '.$asset->name,
            created_by_user_id: $user->id,
            is_posted: true,
            lines: $lines,
            source_type: Asset::class,
            source_id: $asset->id
        );

        return $this->createJournalEntryAction->execute($journalEntryDTO);
    }
}
