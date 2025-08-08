<?php

namespace App\Actions\Accounting;

use App\Models\JournalEntry;
use App\Models\User;
use App\Models\VendorBill;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateJournalEntryForExpenseBillAction
{
    public function __construct(private readonly CreateJournalEntryAction $createJournalEntryAction)
    {
    }

    public function execute(VendorBill $vendorBill, User $user): JournalEntry
    {
        return DB::transaction(function () use ($vendorBill, $user) {
            $vendorBill->load('company', 'currency', 'lines.tax');

            $company = $vendorBill->company;
            $currency = $vendorBill->currency;
            $apAccountId = $company->default_accounts_payable_id;

            if (!$apAccountId) {
                throw new RuntimeException('Default Accounts Payable account is not configured for this company.');
            }

            $expenseLines = $vendorBill->lines->where('product.type', '!=', 'storable');

            $lineDTOs = [];
            $totalDebit = Money::of(0, $currency->code);

            foreach ($expenseLines as $line) {
                // Debit the expense account
                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: $line->expense_account_id,
                    debit: $line->subtotal,
                    credit: Money::of(0, $currency->code),
                    description: $line->description,
                    partner_id: null,
                    analytic_account_id: null,
                );
                $totalDebit = $totalDebit->plus($line->subtotal);

                // Handle tax if applicable
                if ($line->tax_id && $line->total_line_tax->isPositive()) {
                    $taxAccountId = $company->default_tax_receivable_id; // Or a more specific tax account if needed
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $taxAccountId,
                        debit: $line->total_line_tax,
                        credit: Money::of(0, $currency->code),
                        description: "Tax for: {$line->description}",
                        partner_id: null,
                        analytic_account_id: null,
                    );
                    $totalDebit = $totalDebit->plus($line->total_line_tax);
                }
            }

            // Credit Accounts Payable for the total amount
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $apAccountId,
                debit: Money::of(0, $currency->code),
                credit: $totalDebit,
                description: 'Accounts Payable',
                partner_id: $vendorBill->partner_id,
                analytic_account_id: null,
            );

            $journalEntryDTO = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $company->default_purchase_journal_id,
                currency_id: $currency->id,
                entry_date: $vendorBill->accounting_date,
                reference: $vendorBill->bill_reference,
                description: 'Vendor Bill ' . $vendorBill->bill_reference,
                source_type: VendorBill::class,
                source_id: $vendorBill->id,
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lineDTOs,
            );

            return $this->createJournalEntryAction->execute($journalEntryDTO);
        });
    }
}
