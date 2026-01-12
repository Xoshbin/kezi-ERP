<?php

namespace Modules\Accounting\Services;

use Modules\Accounting\Actions\Deferred\CreateDeferralScheduleAction;
use Modules\Accounting\Actions\Deferred\ProcessDeferredEntriesAction;
use Modules\Accounting\Enums\Deferred\DeferralMethod;
use Modules\Accounting\Models\DeferredItem;
use Modules\Accounting\Models\DeferredLine;
use Modules\Purchase\Models\VendorBillLine;
use Modules\Sales\Models\InvoiceLine;

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
            'original_amount' => $line->subtotal_company_currency,
            'deferred_amount' => $line->subtotal_company_currency,
            'start_date' => $line->deferred_start_date,
            'end_date' => $line->deferred_end_date,
            'method' => DeferralMethod::Linear,
            'deferred_account_id' => $line->income_account_id, // The account on the line (Liability)
            'recognition_account_id' => $line->product->income_account_id, // The target Revenue account
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
            'original_amount' => $line->subtotal_company_currency,
            'deferred_amount' => $line->subtotal_company_currency,
            'start_date' => $line->deferred_start_date,
            'end_date' => $line->deferred_end_date,
            'method' => DeferralMethod::Linear,
            'deferred_account_id' => $line->expense_account_id, // The account on the line (Asset)
            'recognition_account_id' => $line->product->expense_account_id, // The target Expense account
            'source_type' => VendorBillLine::class,
            'source_id' => $line->id,
        ]);

        $this->generateSchedule($deferredItem);

        return $deferredItem;
    }
}
