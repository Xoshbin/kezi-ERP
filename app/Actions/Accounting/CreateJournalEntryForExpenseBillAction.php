<?php

namespace App\Actions\Accounting;

use App\Models\JournalEntry;
use App\Models\User;
use App\Models\VendorBill;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Brick\Money\Money;
use Brick\Math\RoundingMode;
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
            $vendorBill->load('company.currency', 'currency', 'lines.tax', 'vendor');

            $company = $vendorBill->company;
            $baseCurrency = $company->currency;
            $foreignCurrency = $vendorBill->currency;

            // Use vendor's individual payable account if available, otherwise fall back to default
            $apAccountId = $vendorBill->vendor->payable_account_id ?? $company->default_accounts_payable_id;

            if (!$apAccountId) {
                throw new RuntimeException('Default Accounts Payable account is not configured for this company.');
            }

            // Determine the exchange rate. If it's the same currency, the rate is 1.
            $exchangeRate = ($baseCurrency->id === $foreignCurrency->id) ? 1.0 : $foreignCurrency->exchange_rate;

            $expenseLines = $vendorBill->lines->where('product.type', '!=', 'storable');

            $lineDTOs = [];
            $zeroAmountInBase = Money::of(0, $baseCurrency->code);
            $totalDebit = Money::zero($baseCurrency->code);

            foreach ($expenseLines as $line) {
                // Convert line amounts to base currency
                $subtotalInBase = Money::of($line->subtotal->getAmount(), $baseCurrency->code)->multipliedBy($exchangeRate, RoundingMode::HALF_UP);

                // Debit the expense account
                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: $line->expense_account_id,
                    debit: $subtotalInBase,
                    credit: $zeroAmountInBase,
                    description: $line->description,
                    partner_id: null,
                    analytic_account_id: null,
                    original_currency_amount: $line->subtotal->getAmount()->toFloat(),
                    exchange_rate_at_transaction: $exchangeRate,
                );
                $totalDebit = $totalDebit->plus($subtotalInBase);

                // Handle tax if applicable
                if ($line->tax_id && $line->total_line_tax->isPositive()) {
                    $taxAmountInBase = Money::of($line->total_line_tax->getAmount(), $baseCurrency->code)->multipliedBy($exchangeRate, RoundingMode::HALF_UP);
                    $taxAccountId = $company->default_tax_receivable_id; // Or a more specific tax account if needed
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $taxAccountId,
                        debit: $taxAmountInBase,
                        credit: $zeroAmountInBase,
                        description: "Tax for: {$line->description}",
                        partner_id: null,
                        analytic_account_id: null,
                        original_currency_amount: $line->total_line_tax->getAmount()->toFloat(),
                        exchange_rate_at_transaction: $exchangeRate,
                    );
                    $totalDebit = $totalDebit->plus($taxAmountInBase);
                }
            }

            // Credit Accounts Payable for the total amount
            $totalOriginalAmount = $vendorBill->total_amount->getAmount()->toFloat();
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $apAccountId,
                debit: $zeroAmountInBase,
                credit: $totalDebit,
                description: 'Accounts Payable',
                partner_id: $vendorBill->partner_id,
                analytic_account_id: null,
                original_currency_amount: $totalOriginalAmount,
                exchange_rate_at_transaction: $exchangeRate,
            );

            $journalEntryDTO = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $company->default_purchase_journal_id,
                currency_id: $baseCurrency->id, // Journal entry is always in company base currency
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
