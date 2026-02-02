<?php

namespace Kezi\Accounting\Actions\Recurring;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Kezi\Accounting\Models\RecurringTemplate;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\PaymentTerm;
use Kezi\Sales\Actions\Sales\CreateInvoiceAction;
use Kezi\Sales\DataTransferObjects\Sales\CreateInvoiceDTO;
use Kezi\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO;

class ProcessRecurringTransactionAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction,
        private readonly CreateInvoiceAction $createInvoiceAction
    ) {}

    public function execute(RecurringTemplate $template, Carbon $runDate): void
    {
        DB::transaction(function () use ($template, $runDate) {
            match ($template->target_type->value) {
                'journal_entry' => $this->processJournalEntry($template),
                'invoice' => $this->processInvoice($template),
            };

            // Update next run date
            $template->update([
                'next_run_date' => $template->frequency->nextDate($runDate, $template->interval),
            ]);
        });
    }

    private function processJournalEntry(RecurringTemplate $template): void
    {
        $data = $template->template_data;
        $currency = Currency::findOrFail($data['currency_id']);
        $currencyCode = $currency->code;

        $lines = collect($data['lines'])->map(function ($line) use ($currencyCode) {
            return new CreateJournalEntryLineDTO(
                account_id: $line['account_id'],
                debit: Money::of($line['debit'], $currencyCode),
                credit: Money::of($line['credit'], $currencyCode),
                description: $line['description'] ?? null,
                partner_id: $line['partner_id'] ?? null,
                analytic_account_id: $line['analytic_account_id'] ?? null,
            );
        })->toArray();

        $dto = new CreateJournalEntryDTO(
            company_id: $template->company_id,
            journal_id: $data['journal_id'],
            currency_id: $data['currency_id'],
            entry_date: Carbon::now()->format('Y-m-d'),
            reference: 'Recurring: '.$template->name,
            description: $data['description'] ?? $template->name,
            created_by_user_id: $template->created_by_user_id ?? 1,
            is_posted: true,
            lines: $lines,
            source_type: 'RecurringTemplate',
            source_id: $template->id,
        );

        $this->createJournalEntryAction->execute($dto);
    }

    private function processInvoice(RecurringTemplate $template): void
    {
        $data = $template->template_data;
        $currency = Currency::findOrFail($data['currency_id']);
        $currencyCode = $currency->code;

        $lines = collect($data['lines'])->map(function ($line) use ($currencyCode) {
            return new CreateInvoiceLineDTO(
                description: $line['description'],
                quantity: $line['quantity'],
                unit_price: Money::of($line['unit_price'], $currencyCode),
                income_account_id: $line['income_account_id'],
                product_id: $line['product_id'] ?? null,
                tax_id: $line['tax_id'] ?? null,
            );
        })->toArray();

        $invoiceDate = Carbon::now();
        $dueDate = $invoiceDate->copy();

        if (isset($data['payment_term_id'])) {
            $paymentTerm = PaymentTerm::find($data['payment_term_id']);
            if ($paymentTerm) {
                $installments = $paymentTerm->calculateInstallments($invoiceDate, Money::of(1, $currencyCode));
                if (! empty($installments)) {
                    // Use the last installment due date as the invoice due date
                    $dueDate = end($installments)['due_date'];
                }
            }
        }

        $dto = new CreateInvoiceDTO(
            company_id: $template->company_id,
            customer_id: $data['customer_id'],
            currency_id: $data['currency_id'],
            invoice_date: $invoiceDate->format('Y-m-d'),
            due_date: $dueDate->format('Y-m-d'),
            lines: $lines,
            fiscal_position_id: $data['fiscal_position_id'] ?? null,
            payment_term_id: $data['payment_term_id'] ?? null,
        );

        $this->createInvoiceAction->execute($dto);
    }
}
