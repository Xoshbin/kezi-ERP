<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateJournalEntryForInvoiceAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction
    ) {}

    public function execute(Invoice $invoice, User $user): JournalEntry
    {
        return DB::transaction(function () use ($invoice, $user) {
            // 1. Load all necessary related data for efficiency.
            $invoice->load('company', 'currency', 'invoiceLines.tax', 'invoiceLines.incomeAccount', 'customer');

            $company = $invoice->company;
            $currency = $invoice->currency;

            // Use customer's individual receivable account if available, otherwise fall back to default
            $arAccountId = $invoice->customer->receivable_account_id ?? $company->default_accounts_receivable_id;
            $salesJournalId = $company->default_sales_journal_id;

            if (! $arAccountId || ! $salesJournalId) {
                throw new RuntimeException('Default Accounts Receivable or Sales Journal is not configured for this company.');
            }

            // 2. Prepare the lines for the journal entry based on accounting rules.
            $lineDTOs = [];
            $totalDebit = Money::of(0, $currency->code);

            // Rule: Each invoice line CREDITS the respective Income Account.
            foreach ($invoice->invoiceLines as $line) {
                // Credit the income account
                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: $line->income_account_id,
                    debit: Money::of(0, $currency->code),
                    credit: $line->subtotal,
                    description: $line->description,
                    partner_id: null,
                    analytic_account_id: null,
                );

                $totalDebit = $totalDebit->plus($line->subtotal);

                // Credit the tax account if there's tax
                if ($line->total_line_tax->isPositive() && $line->tax) {
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $line->tax->tax_account_id,
                        debit: Money::of(0, $currency->code),
                        credit: $line->total_line_tax,
                        description: 'Tax for ' . $invoice->invoice_number,
                        partner_id: null,
                        analytic_account_id: null,
                    );

                    $totalDebit = $totalDebit->plus($line->total_line_tax);
                }
            }

            // Rule: The total invoice amount DEBITS Accounts Receivable.
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $arAccountId,
                debit: $totalDebit,
                credit: Money::of(0, $currency->code),
                description: 'A/R for ' . $invoice->invoice_number,
                partner_id: $invoice->customer_id,
                analytic_account_id: null,
            );

            // 3. Create the parent JournalEntry record.
            $journalEntryDTO = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $salesJournalId,
                currency_id: $currency->id,
                entry_date: $invoice->invoice_date,
                reference: $invoice->invoice_number,
                description: 'Invoice ' . $invoice->invoice_number,
                source_type: Invoice::class,
                source_id: $invoice->id,
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lineDTOs,
                exchange_rate: $invoice->exchange_rate_at_creation,
            );

            return $this->createJournalEntryAction->execute($journalEntryDTO);
        });
    }
}
