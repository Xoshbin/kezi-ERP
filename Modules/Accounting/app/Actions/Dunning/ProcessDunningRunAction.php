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
    public function execute(int $companyId): void
    {
        // 1. Get all dunning levels for the company, ordered by days_overdue desc
        $levels = DunningLevel::where('company_id', $companyId)
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
            ->with(['customer', 'dunningLevel'])
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

            if ($targetLevel->send_email && $invoice->customer->email) {
                Mail::to($invoice->customer->email)->send(new DunningReminderMail($invoice, $targetLevel));
                Log::info("Sent Dunning Email for Invoice {$invoiceIdentifier} at Level {$targetLevel->name}");
            }
        });
    }
}
