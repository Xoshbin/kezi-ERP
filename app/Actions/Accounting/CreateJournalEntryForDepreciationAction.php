<?php

namespace App\Actions\Accounting;

use App\Models\DepreciationEntry;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\JournalEntryService;
use Brick\Money\Money;
use Illuminate\Support\Facades\App;

class CreateJournalEntryForDepreciationAction
{
    protected JournalEntryService $journalEntryService;

    public function __construct()
    {
        // We still use the generic service, but it's now encapsulated here.
        $this->journalEntryService = App::make(JournalEntryService::class);
    }

    public function execute(DepreciationEntry $entry, User $user): JournalEntry
    {
        // 1. Load necessary relationships for context.
        $entry->load('asset.company.currency');
        $asset = $entry->asset;
        $company = $asset->company;
        $journalId = $company->default_depreciation_journal_id;
        $currencyCode = $company->currency->code;

        if (!$journalId) {
            throw new \RuntimeException('Default depreciation journal is not configured for this company.');
        }

        // 2. Build the journal entry lines based on depreciation accounting rules.
        $lines = [
            // Rule: DEBIT the Depreciation Expense account.
            [
                'account_id' => $asset->depreciation_expense_account_id,
                'debit' => $entry->amount,
                'credit' => Money::of(0, $currencyCode),
                'description' => 'Depreciation Expense for ' . $asset->name,
            ],
            // Rule: CREDIT the Accumulated Depreciation contra-asset account.
            [
                'account_id' => $asset->accumulated_depreciation_account_id,
                'credit' => $entry->amount,
                'debit' => Money::of(0, $currencyCode),
                'description' => 'Accumulated Depreciation for ' . $asset->name,
            ],
        ];

        // 3. Prepare the data payload for the generic JournalEntryService.
        $journalEntryData = [
            'company_id' => $asset->company_id,
            'journal_id' => $journalId,
            'entry_date' => $entry->depreciation_date,
            'reference' => 'DEPR/' . $asset->name . '/' . $entry->depreciation_date->format('Y-m'),
            'description' => 'Depreciation for ' . $asset->name,
            'source_type' => DepreciationEntry::class,
            'source_id' => $entry->id,
            'created_by_user_id' => $user->id,
            'lines' => $lines,
            'currency_id' => $company->currency_id,
        ];

        // 4. Execute the generic service and return the result.
        // We pass 'true' to post it immediately.
        return $this->journalEntryService->create($journalEntryData, true);
    }
}
