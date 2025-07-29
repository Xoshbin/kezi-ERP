<?php

namespace App\Actions\Accounting;

use App\Models\AdjustmentDocument;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\JournalEntryService;
use Brick\Money\Money;
use Illuminate\Support\Facades\App;

class CreateJournalEntryForAdjustmentAction
{
    protected JournalEntryService $journalEntryService;

    public function __construct()
    {
        $this->journalEntryService = App::make(JournalEntryService::class);
    }

    public function execute(AdjustmentDocument $adjustment, User $user): JournalEntry
    {
        // 1. Load necessary relationships for context.
        $adjustment->load('company.currency', 'currency');
        $company = $adjustment->company;
        $currencyCode = $adjustment->currency->code;

        // 2. Get the required default accounts from the company.
        $arAccountId = $company->default_accounts_receivable_id;
        $salesDiscountAccountId = $company->default_sales_discount_account_id;
        $taxAccountId = $company->default_tax_account_id;
        $salesJournalId = $company->default_sales_journal_id;

        if (!$arAccountId || !$salesDiscountAccountId || !$taxAccountId || !$salesJournalId) {
            throw new \RuntimeException('Default accounting accounts for adjustments are not configured for this company.');
        }

        // 3. Build the journal entry lines based on credit note accounting rules (reversing a sale).
        $lines = [];
        $subtotal = $adjustment->total_amount->minus($adjustment->total_tax);

        // Rule: DEBIT the Sales Discount/Contra-Revenue account.
        $lines[] = [
            'account_id' => $salesDiscountAccountId,
            'debit' => $subtotal,
            'credit' => Money::of(0, $currencyCode),
        ];

        // Rule: DEBIT Tax Payable to reduce it (if there is tax).
        if ($adjustment->total_tax->isPositive()) {
            $lines[] = [
                'account_id' => $taxAccountId,
                'debit' => $adjustment->total_tax,
                'credit' => Money::of(0, $currencyCode),
            ];
        }

        // Rule: CREDIT Accounts Receivable to reduce the customer's debt.
        $lines[] = [
            'account_id' => $arAccountId,
            'credit' => $adjustment->total_amount,
            'debit' => Money::of(0, $currencyCode),
        ];

        // 4. Prepare the data payload for the generic JournalEntryService.
        $journalEntryData = [
            'company_id' => $adjustment->company_id,
            'journal_id' => $salesJournalId,
            'entry_date' => $adjustment->posted_at,
            'reference' => 'CN-' . $adjustment->reference_number,
            'description' => 'Credit Note ' . $adjustment->reference_number,
            'source_type' => AdjustmentDocument::class,
            'source_id' => $adjustment->id,
            'created_by_user_id' => $user->id,
            'lines' => $lines,
        ];

        // 5. Execute the generic service and return the result.
        return $this->journalEntryService->create($journalEntryData, true);
    }
}
