<?php

namespace Modules\Accounting\Actions\Recurring;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Modules\Accounting\Enums\Accounting\RecurringFrequency;
use Modules\Accounting\Enums\Accounting\RecurringStatus;
use Modules\Accounting\Enums\Accounting\RecurringTargetType;
use Modules\Accounting\Models\RecurringTemplate;
use Modules\Foundation\Models\Currency;
use Modules\Sales\Actions\Sales\CreateInvoiceAction;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceDTO;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO;

class ProcessRecurringTransactionAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction,
        private readonly CreateInvoiceAction $createInvoiceAction,
    ) {}

    public function execute(RecurringTemplate $template): void
    {
        if ($template->status !== RecurringStatus::Active) {
            return;
        }

        DB::transaction(function () use ($template) {
            match ($template->target_type) {
                RecurringTargetType::JournalEntry => $this->processJournalEntry($template),
                RecurringTargetType::Invoice => $this->processInvoice($template),
            };

            $this->updateNextRunDate($template);
        });
    }

    private function processJournalEntry(RecurringTemplate $template): void
    {
        $data = $template->template_data;
        $company = Company::findOrFail($template->company_id);
        $currency = Currency::findOrFail($data['currency_id']);

        $lines = collect($data['lines'])->map(function ($line) use ($currency) {
            return new CreateJournalEntryLineDTO(
                account_id: $line['account_id'],
                debit: Money::of($line['debit'], $currency->code),
                credit: Money::of($line['credit'], $currency->code),
                description: $line['description'] ?? null,
                partner_id: $line['partner_id'] ?? null,
                analytic_account_id: $line['analytic_account_id'] ?? null,
            );
        })->toArray();

        $dto = new CreateJournalEntryDTO(
            company_id: $template->company_id,
            journal_id: $data['journal_id'],
            currency_id: $data['currency_id'],
            entry_date: Carbon::now()->format('Y-m-d'), // Generate for today
            reference: $data['reference'] ?? null,
            description: $data['description'] ?? "Recurring: {$template->name}",
            created_by_user_id: $template->created_by_user_id ?? 1, // Fallback to system user or null handling
            is_posted: false, // Always draft for safety
            lines: $lines,
        );

        $this->createJournalEntryAction->execute($dto);
    }

    private function processInvoice(RecurringTemplate $template): void
    {
        $data = $template->template_data;
        $company = Company::findOrFail($template->company_id);
        $currencyCode = Currency::findOrFail($data['currency_id'])->code;

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

        // Calculate due date based on payment terms if needed, for now default to invoice date
        $invoiceDate = Carbon::now()->format('Y-m-d');

        $dto = new CreateInvoiceDTO(
            company_id: $template->company_id,
            customer_id: $data['customer_id'],
            currency_id: $data['currency_id'],
            invoice_date: $invoiceDate,
            due_date: $invoiceDate, // Should ideally calculate from payment term
            lines: $lines,
            fiscal_position_id: $data['fiscal_position_id'] ?? null,
            payment_term_id: $data['payment_term_id'] ?? null,
        );

        $this->createInvoiceAction->execute($dto);
    }

    private function updateNextRunDate(RecurringTemplate $template): void
    {
        $nextDate = Carbon::parse($template->next_run_date);

        match ($template->frequency) {
            RecurringFrequency::Daily => $nextDate->addDays($template->interval),
            RecurringFrequency::Weekly => $nextDate->addWeeks($template->interval),
            RecurringFrequency::Monthly => $nextDate->addMonths($template->interval),
            RecurringFrequency::Yearly => $nextDate->addYears($template->interval),
        };

        if ($template->end_date && $nextDate->gt(Carbon::parse($template->end_date))) {
            $template->status = RecurringStatus::Completed;
        } else {
            $template->next_run_date = $nextDate;
        }

        $template->save();
    }
}
