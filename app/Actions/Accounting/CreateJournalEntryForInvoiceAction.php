<?php

namespace App\Actions\Accounting;

use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\User;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;

class CreateJournalEntryForInvoiceAction
{
    public function execute(Invoice $invoice, User $user): JournalEntry
    {
        return DB::transaction(function () use ($invoice, $user) {
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
            $lines[] = [
                'account_id' => $arAccountId,
                'debit' => $invoice->total_amount->multipliedBy($exchangeRate, RoundingMode::HALF_UP),
                'credit' => $zeroAmountInBase,
                'description' => 'A/R for ' . $invoice->invoice_number,
                'currency_id' => $foreignCurrency->id,
                'original_currency_amount' => $invoice->total_amount,
                'exchange_rate_at_transaction' => $exchangeRate,
            ];

            // Rule: Each line item's subtotal CREDITS its respective income account.
            // Rule: Each line item's tax CREDITS the tax account.
            foreach ($invoice->invoiceLines as $line) {
                $lines[] = [
                    'account_id' => $line->income_account_id,
                    'credit' => $line->subtotal->multipliedBy($exchangeRate, RoundingMode::HALF_UP),
                    'debit' => $zeroAmountInBase,
                    'description' => $line->description,
                    'currency_id' => $foreignCurrency->id,
                    'original_currency_amount' => $line->subtotal,
                    'exchange_rate_at_transaction' => $exchangeRate,
                ];

                if ($line->total_line_tax->isPositive() && $line->tax) {
                    $lines[] = [
                        'account_id' => $line->tax->tax_account_id,
                        'credit' => $line->total_line_tax->multipliedBy($exchangeRate, RoundingMode::HALF_UP),
                        'debit' => $zeroAmountInBase,
                        'description' => 'Tax for ' . $invoice->invoice_number,
                        'currency_id' => $foreignCurrency->id,
                        'original_currency_amount' => $line->total_line_tax,
                        'exchange_rate_at_transaction' => $exchangeRate,
                    ];
                }
            }

            // 4. Create the parent JournalEntry record.
            $totalInBase = $invoice->total_amount->multipliedBy($exchangeRate, RoundingMode::HALF_UP);
            $journalEntry = JournalEntry::create([
                'company_id' => $company->id,
                'journal_id' => $salesJournalId,
                'currency_id' => $baseCurrency->id, // The Journal Entry is always in the company's base currency
                'entry_date' => $invoice->posted_at,
                'reference' => $invoice->invoice_number,
                'description' => 'Invoice ' . $invoice->invoice_number,
                'source_type' => get_class($invoice),
                'source_id' => $invoice->id,
                'created_by_user_id' => $user->id,
                'total_debit' => $totalInBase,
                'total_credit' => $totalInBase,
                'is_posted' => true,
            ]);

            // 5. Add currency_id to each line for the MoneyCast before saving.
            $linesToCreate = array_map(fn($line) => $line + ['currency_id' => $baseCurrency->id], $lines);

            $journalEntry->lines()->createMany($linesToCreate);

            return $journalEntry;
        });
    }
}
