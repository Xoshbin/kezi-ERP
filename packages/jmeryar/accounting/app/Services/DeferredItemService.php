<?php

namespace Jmeryar\Accounting\Services;

use Jmeryar\Accounting\Actions\Deferred\CreateDeferralScheduleAction;
use Jmeryar\Accounting\Actions\Deferred\ProcessDeferredEntriesAction;
use Jmeryar\Accounting\Enums\Deferred\DeferralMethod;
use Jmeryar\Accounting\Models\DeferredItem;
use Jmeryar\Accounting\Models\DeferredLine;
use Jmeryar\Purchase\Models\VendorBillLine;
use Jmeryar\Sales\Models\InvoiceLine;

class DeferredItemService
{
    public function __construct(
        protected CreateDeferralScheduleAction $createDeferralScheduleAction,
        protected ProcessDeferredEntriesAction $processDeferredEntriesAction
    ) {}

    public function generateSchedule(DeferredItem $deferredItem): void
    {
        $this->createDeferralScheduleAction->execute($deferredItem);
    }

    public function processDueEntries(): int
    {
        // Find all draft lines with date <= today
        $lines = DeferredLine::where('status', 'draft')
            ->whereDate('date', '<=', now())
            ->get();

        $count = 0;
        foreach ($lines as $line) {
            $this->processDeferredEntriesAction->execute($line);
            $count++;
        }

        return $count;
    }

    public function createFromInvoiceLine(InvoiceLine $line): ?DeferredItem
    {
        if (! $line->deferred_start_date || ! $line->deferred_end_date) {
            return null;
        }

        // Validate we have a product to determine recognition account
        if (! $line->product || ! $line->product->income_account_id) {
            return null;
        }

        $deferredItem = DeferredItem::create([
            'company_id' => $line->company_id,
            'type' => 'revenue',
            'name' => $line->description ?? 'Deferred Revenue '.$line->invoice->generated_number,
            'original_amount' => $line->subtotal_company_currency ?? $line->subtotal->getAmount(), // Fallback if company currency is null (safer)
            'deferred_amount' => $line->subtotal_company_currency ?? $line->subtotal->getAmount(),
            'start_date' => $line->deferred_start_date,
            'end_date' => $line->deferred_end_date,
            'method' => DeferralMethod::Linear,
            'deferred_account_id' => $line->product->deferred_revenue_account_id,
            'recognition_account_id' => $line->product->income_account_id,
            'source_type' => InvoiceLine::class,
            'source_id' => $line->id,
        ]);

        $this->generateSchedule($deferredItem);

        return $deferredItem;
    }

    public function createFromVendorBillLine(VendorBillLine $line): ?DeferredItem
    {
        if (! $line->deferred_start_date || ! $line->deferred_end_date) {
            return null;
        }

        if (! $line->product || ! $line->product->expense_account_id) {
            return null;
        }

        $deferredItem = DeferredItem::create([
            'company_id' => $line->company_id,
            'type' => 'expense',
            'name' => $line->description ?? 'Deferred Expense '.$line->vendorBill->reference,
            'original_amount' => $line->subtotal_company_currency ?? $line->subtotal->getAmount(),
            'deferred_amount' => $line->subtotal_company_currency ?? $line->subtotal->getAmount(),
            'start_date' => $line->deferred_start_date,
            'end_date' => $line->deferred_end_date,
            'method' => DeferralMethod::Linear,
            'deferred_account_id' => $line->product->deferred_expense_account_id,
            'recognition_account_id' => $line->product->expense_account_id,
            'source_type' => VendorBillLine::class,
            'source_id' => $line->id,
        ]);

        $this->generateSchedule($deferredItem);

        return $deferredItem;
    }
}
