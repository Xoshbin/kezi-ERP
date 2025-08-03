<?php

namespace App\Actions\Accounting;

use App\Models\Asset;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\JournalEntryService;
use Brick\Money\Money;
use Illuminate\Support\Facades\App;
use RuntimeException;

class CreateJournalEntryForAssetAcquisitionAction
{
    protected JournalEntryService $journalEntryService;

    public function __construct()
    {
        $this->journalEntryService = App::make(JournalEntryService::class);
    }

    public function execute(Asset $asset, User $user): JournalEntry
    {
        $company = $asset->company;
        $currencyCode = $asset->currency->code;

        // Use the company's default Accounts Payable as the credit account for the acquisition.
        $payableAccountId = $company->default_accounts_payable_id;
        if (!$payableAccountId) {
            throw new RuntimeException('Default Accounts Payable is not configured for this company.');
        }

        $lines = [
            // Rule: DEBIT the Asset's own GL account to record its value on the balance sheet.
            [
                'account_id' => $asset->asset_account_id,
                'debit' => $asset->purchase_value,
                'credit' => Money::zero($currencyCode),
                'description' => 'Asset Acquisition: ' . $asset->name,
            ],
            // Rule: CREDIT Accounts Payable, creating a liability to be paid off.
            [
                'account_id' => $payableAccountId,
                'credit' => $asset->purchase_value,
                'debit' => Money::zero($currencyCode),
                'description' => 'Acquisition of Asset: ' . $asset->name,
            ],
        ];

        $journalEntryData = [
            'company_id' => $asset->company_id,
            'currency_id' => $asset->currency_id,
            // We assume a 'Miscellaneous' or 'General' journal should be used for manual assets.
            // For now, we'll use the depreciation journal as a placeholder.
            'journal_id' => $company->default_depreciation_journal_id,
            'entry_date' => $asset->purchase_date,
            'reference' => 'ASSET/' . $asset->id,
            'description' => 'Acquisition of Asset: ' . $asset->name,
            'source_type' => Asset::class,
            'source_id' => $asset->id,
            'created_by_user_id' => $user->id,
            'lines' => $lines,
        ];

        return $this->journalEntryService->create($journalEntryData, true);
    }
}
