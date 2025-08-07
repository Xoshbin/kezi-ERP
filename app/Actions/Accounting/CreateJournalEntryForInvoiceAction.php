<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\User;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

class CreateJournalEntryForInvoiceAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction
    ) {
    }

    public function execute(Invoice $invoice, User $user): JournalEntry
    {
        // 1. Load all necessary related data for efficiency.
        $invoice->load('company.currency', 'currency', 'invoiceLines.tax', 'invoiceLines.incomeAccount');

        $company = $invoice->company;
        $baseCurrency = $company->currency;
        $foreignCurrency = $invoice->currency;
        $arAccountId = $company->default_accounts_receivable_id;
        $salesJournalId = $company->default_sales_journal_id;

        if (!$arAccountId || !$salesJournalId) {
            throw new \RuntimeException('Default Accounts Receivable or Sales Journal is not configured for this company.');
        }

        // 2. Determine the exchange rate. If it's the same currency, the rate is 1.
        $exchangeRate = ($baseCurrency->id === $foreignCurrency->id) ? 1.0 : $foreignCurrency->exchange_rate;

        // 3. Prepare the lines for the journal entry based on accounting rules.
        $lines = [];
        $zeroAmountInBase = Money::of(0, $baseCurrency->code);

        // Rule: The total invoice amount DEBITS Accounts Receivable.
        // FIX: Calculate the total debit by summing the lines, ensuring it always matches the credit side.
        $totalDebit = Money::zero($baseCurrency->code);
        foreach ($invoice->invoiceLines as $line) {
            $totalDebit = $totalDebit->plus(Money::of($line->subtotal->getAmount(), $baseCurrency->code)->multipliedBy($exchangeRate, RoundingMode::HALF_UP));
            if ($line->total_line_tax->isPositive()) {
                $totalDebit = $totalDebit->plus(Money::of($line->total_line_tax->getAmount(), $baseCurrency->code)->multipliedBy($exchangeRate, RoundingMode::HALF_UP));
            }
        }

        $lines[] = new CreateJournalEntryLineDTO(
            account_id: $arAccountId,
            debit: $totalDebit,
            credit: $zeroAmountInBase,
            description: 'A/R for ' . $invoice->invoice_number,
            partner_id: null,
            analytic_account_id: null,
        );

        // Rule: Each line item's subtotal CREDITS its respective income account.
        // Rule: Each line item's tax CREDITS the tax account.
        foreach ($invoice->invoiceLines as $line) {
            $lines[] = new CreateJournalEntryLineDTO(
                account_id: $line->income_account_id,
                credit: Money::of($line->subtotal->getAmount(), $baseCurrency->code)->multipliedBy($exchangeRate, RoundingMode::HALF_UP),
                debit: $zeroAmountInBase,
                description: $line->description,
                partner_id: null,
                analytic_account_id: null,
            );

            if ($line->total_line_tax->isPositive() && $line->tax) {
                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $line->tax->tax_account_id,
                    credit: Money::of($line->total_line_tax->getAmount(), $baseCurrency->code)->multipliedBy($exchangeRate, RoundingMode::HALF_UP),
                    debit: $zeroAmountInBase,
                    description: 'Tax for ' . $invoice->invoice_number,
                    partner_id: null,
                    analytic_account_id: null,
                );
            }
        }

        // 4. Create the parent JournalEntry record.
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $company->id,
            journal_id: $salesJournalId,
            currency_id: $baseCurrency->id, // The Journal Entry is always in the company's base currency
            entry_date: $invoice->posted_at,
            reference: $invoice->invoice_number,
            description: 'Invoice ' . $invoice->invoice_number,
            created_by_user_id: $user->id,
            is_posted: true,
            lines: $lines,
            source_type: get_class($invoice),
            source_id: $invoice->id,
        );

        return $this->createJournalEntryAction->execute($journalEntryDTO);
    }
}
