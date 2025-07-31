<?php

namespace App\Actions\Accounting;

use App\Models\JournalEntry;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\JournalEntryService;
use Brick\Money\Money;
use Illuminate\Support\Facades\App;

class CreateJournalEntryForVendorBillAction
{
    protected JournalEntryService $journalEntryService;

    public function __construct()
    {
        $this->journalEntryService = App::make(JournalEntryService::class);
    }

    public function execute(VendorBill $vendorBill, User $user): JournalEntry
    {
        $vendorBill->load('company', 'currency', 'lines.tax');

        \Illuminate\Support\Facades\Log::info('VendorBill total_amount in action: ' . $vendorBill->total_amount->getAmount());

        $company = $vendorBill->company;
        $currency = $vendorBill->currency;
        $currencyCode = $currency->code;
        $apAccountId = $company->default_accounts_payable_id;
        $taxAccountId = $company->default_tax_receivable_id;
        $purchaseJournalId = $company->default_purchase_journal_id;

        if (!$apAccountId || !$taxAccountId || !$purchaseJournalId) {
            throw new \RuntimeException('Default accounting accounts for purchases are not configured for this company.');
        }

        $lines = [];
        $zeroAmount = Money::of(0, $currencyCode);

        // Rule: The total bill amount CREDITS Accounts Payable.
        $lines[] = [
            'account_id' => $apAccountId,
            'credit' => $vendorBill->total_amount,
            'debit' => $zeroAmount,
            'description' => 'Accounts Payable',
        ];

        // Rule: Each line item's subtotal DEBITS its expense account.
        // Rule: Each line item's tax DEBITS the tax receivable account.
        foreach ($vendorBill->lines as $line) {
            $lines[] = [
                'account_id' => $line->expense_account_id,
                'debit' => $line->subtotal,
                'credit' => $zeroAmount,
                'description' => $line->description,
            ];

            if ($line->total_line_tax->isPositive()) {
                $lines[] = [
                    'account_id' => $taxAccountId,
                    'debit' => $line->total_line_tax,
                    'credit' => $zeroAmount,
                    'description' => 'Tax for bill ' . $vendorBill->bill_reference,
                ];
            }
        }

        $journalEntryData = [
            'company_id' => $company->id,
            'journal_id' => $purchaseJournalId,
            'currency_id' => $currency->id,
            'entry_date' => $vendorBill->posted_at,
            'reference' => $vendorBill->bill_reference,
            'description' => 'Vendor Bill ' . $vendorBill->bill_reference,
            'source_type' => get_class($vendorBill),
            'source_id' => $vendorBill->id,
            'created_by_user_id' => $user->id,
            'lines' => $lines,
        ];

        // 1. Call the service. This creates the entry and lines, but the observer
        //    on the JournalEntry model will incorrectly set the totals to zero.
        return $this->journalEntryService->create($journalEntryData, true);
    }
}
