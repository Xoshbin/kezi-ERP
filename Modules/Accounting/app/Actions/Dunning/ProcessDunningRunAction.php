<?php

namespace Modules\Accounting\Actions\Dunning;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Accounting\Emails\DunningReminderMail;
use Modules\Accounting\Models\DunningLevel;
use Modules\Sales\Models\Invoice;

class ProcessDunningRunAction
{
    public function __construct(
        private readonly \Modules\Sales\Actions\Sales\CreateInvoiceAction $createInvoiceAction,
    ) {}

    public function execute(int $companyId): void
    {
        // 1. Get all dunning levels for the company, ordered by days_overdue desc
        $levels = DunningLevel::where('company_id', $companyId)
            ->with('feeProduct')
            ->orderBy('days_overdue', 'desc')
            ->get();

        if ($levels->isEmpty()) {
            return;
        }

        // 2. Find eligible invoices
        // Status is Posted, Due Date < Today
        $invoices = Invoice::overdue()
            ->where('company_id', $companyId)
            ->where(function ($query) {
                $query->whereNull('dunning_level_id')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('dunning_level_id')
                            ->where('next_dunning_date', '<=', Carbon::today());
                    });
            })
            ->with(['customer', 'dunningLevel', 'currency'])
            ->get();

        foreach ($invoices as $invoice) {
            $this->processInvoice($invoice, $levels);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, DunningLevel>  $levels
     */
    private function processInvoice(Invoice $invoice, \Illuminate\Support\Collection $levels): void
    {
        $invoiceIdentifier = $invoice->invoice_number ?? "Draft-{$invoice->id}";

        // Ensure we get absolute days overdue
        $daysOverdue = (int) abs($invoice->due_date->diffInDays(Carbon::today(), false));

        Log::info("Processing Invoice {$invoiceIdentifier}, Days Overdue: {$daysOverdue}");

        // Find the highest applicable level
        $targetLevel = $levels->first(function (DunningLevel $level) use ($daysOverdue) {
            return $daysOverdue >= $level->days_overdue;
        });

        if (! $targetLevel) {
            return;
        }

        Log::info("Target Level for Invoice {$invoiceIdentifier}: {$targetLevel->name}");

        $currentLevel = $invoice->dunningLevel;

        // Avoid downgrading or repeating strictly
        if ($currentLevel && $currentLevel->days_overdue >= $targetLevel->days_overdue) {
            return;
        }

        // Apply new level
        DB::transaction(function () use ($invoice, $targetLevel, $invoiceIdentifier) {
            $invoice->update([
                'dunning_level_id' => $targetLevel->id,
                'last_dunning_date' => now(),
                'next_dunning_date' => Carbon::today()->addDay(),
            ]);

            // Handle Late Fee
            if ($targetLevel->charge_fee && $targetLevel->feeProduct) {
                $this->createDebitNote($invoice, $targetLevel);
            }

            if ($targetLevel->send_email && $invoice->customer->email) {
                try {
                    Mail::to($invoice->customer->email)->send(new DunningReminderMail($invoice, $targetLevel));
                    Log::info("Sent Dunning Email for Invoice {$invoiceIdentifier} at Level {$targetLevel->name}");
                } catch (\Exception $e) {
                    Log::error("Failed to send Dunning Email for Invoice {$invoiceIdentifier}: ".$e->getMessage());
                }
            }
        });
    }

    private function createDebitNote(Invoice $invoice, DunningLevel $level): void
    {
        // Calculate Fee Amount
        $currencyCode = $invoice->currency->code;
        $feeAmount = \Brick\Money\Money::of(0, $currencyCode);

        // Fixed Amount
        if ($level->fee_amount && ! $level->fee_amount->isZero()) {
            // Assuming fee_amount is in company base currency, we might need conversion if invoice is different.
            // For MVP simplicity and given requirements, we'll assume fee is defined in invoice currency or simple value injection.
            // Ideally, we should convert. Let's assume the value is raw amount and context agnostic for now or matches invoice currency.
            // To be safe with Money objects:
            $feeAmount = $feeAmount->plus(\Brick\Money\Money::of($level->fee_amount->getAmount(), $currencyCode));
        }

        // Percentage
        if ($level->fee_percentage > 0) {
            $percentageFee = $invoice->total_amount->multipliedBy($level->fee_percentage / 100, \Brick\Math\RoundingMode::HALF_UP);
            $feeAmount = $feeAmount->plus($percentageFee);
        }

        if ($feeAmount->isZero()) {
            return;
        }

        Log::info("Creating Dunning Fee (Debit Note) for Invoice {$invoice->invoice_number}. Amount: {$feeAmount}");

        // Prepare DTOs
        $product = $level->feeProduct;
        $incomeAccount = $product->incomeAccount ?? $product->company->defaultIncomeAccount; // Fallback?

        if (! $incomeAccount && $product->income_account_id) {
            $incomeAccount = \Modules\Accounting\Models\Account::find($product->income_account_id);
        }

        if (! $incomeAccount) {
            Log::warning("No income account found for Dunning Fee Product {$product->name}. Skipping Debit Note.");

            return;
        }

        $lineDto = new \Modules\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO(
            description: 'Late Fee: '.$level->name,
            quantity: 1,
            unit_price: $feeAmount,
            income_account_id: $incomeAccount->id,
            product_id: $product->id,
            tax_id: null, // Fees explicitly usually don't have tax, or we should take from product
        );

        $invoiceDto = new \Modules\Sales\DataTransferObjects\Sales\CreateInvoiceDTO(
            company_id: $invoice->company_id,
            customer_id: $invoice->customer_id,
            currency_id: $invoice->currency_id,
            invoice_date: Carbon::today()->format('Y-m-d'),
            due_date: Carbon::today()->format('Y-m-d'), // Immediate due
            lines: [$lineDto],
            fiscal_position_id: $invoice->fiscal_position_id,
        );

        $debitNote = $this->createInvoiceAction->execute($invoiceDto);

        // Link to original invoice
        $debitNote->update(['source_invoice_id' => $invoice->id]);

        Log::info("Created Debit Note {$debitNote->id} for overdue Invoice {$invoice->invoice_number}");
    }
}
