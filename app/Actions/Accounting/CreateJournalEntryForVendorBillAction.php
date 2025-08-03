<?php

namespace App\Actions\Accounting;

use App\Models\JournalEntry;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\JournalEntryService; // Keep this service for creation
use Brick\Money\Money;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class CreateJournalEntryForVendorBillAction
{
    protected JournalEntryService $journalEntryService;

    public function __construct()
    {
        $this->journalEntryService = App::make(JournalEntryService::class);
    }

    public function execute(VendorBill $vendorBill, User $user): JournalEntry
    {
        // Use a transaction here to ensure atomicity
        return DB::transaction(function () use ($vendorBill, $user) {
            // **THE CRITICAL FIX**: Load all necessary relationships fresh from the database.
            $vendorBill->load('company', 'currency', 'lines.tax');

            $company = $vendorBill->company;
            $currencyCode = $vendorBill->currency->code;
            $apAccountId = $company->default_accounts_payable_id;
            $taxAccountId = $company->default_tax_receivable_id;
            $purchaseJournalId = $company->default_purchase_journal_id;

            if (!$apAccountId || !$taxAccountId || !$purchaseJournalId) {
                throw new \RuntimeException('Default accounting accounts for purchases are not configured for this company.');
            }

            $lines = [];
            $zeroAmount = Money::of(0, $currencyCode);
            $totalDebit = Money::of(0, $currencyCode);
            $totalCredit = Money::of(0, $currencyCode);

            // Calculate totals directly from the fresh lines
            foreach ($vendorBill->lines as $line) {
                // Debit the expense account for the subtotal
                $lines[] = [
                    'account_id' => $line->expense_account_id,
                    'debit' => $line->subtotal,
                    'credit' => $zeroAmount,
                    'description' => $line->description,
                ];
                $totalDebit = $totalDebit->plus($line->subtotal);

                // Debit the tax account if there is tax
                if ($line->total_line_tax->isPositive()) {
                    $lines[] = [
                        'account_id' => $taxAccountId,
                        'debit' => $line->total_line_tax,
                        'credit' => $zeroAmount,
                        'description' => 'Tax for bill ' . $vendorBill->bill_reference,
                    ];
                    $totalDebit = $totalDebit->plus($line->total_line_tax);
                }
            }

            // The total credit is the bill's total amount, which must equal the sum of debits
            $lines[] = [
                'account_id' => $apAccountId,
                'credit' => $totalDebit, // Use the calculated total debit
                'debit' => $zeroAmount,
                'description' => 'Accounts Payable',
            ];
            $totalCredit = $totalCredit->plus($totalDebit);

            // Prepare the data for the JournalEntryService
            $journalEntryData = [
                'company_id' => $company->id,
                'journal_id' => $purchaseJournalId,
                'currency_id' => $vendorBill->currency_id,
                'entry_date' => $vendorBill->posted_at,
                'reference' => $vendorBill->bill_reference,
                'description' => 'Vendor Bill ' . $vendorBill->bill_reference,
                'source_type' => get_class($vendorBill),
                'source_id' => $vendorBill->id,
                'created_by_user_id' => $user->id,
                'lines' => $lines,
            ];

            // The JournalEntryService will now receive a perfectly balanced array
            return $this->journalEntryService->create($journalEntryData, true);
        });
    }
}
