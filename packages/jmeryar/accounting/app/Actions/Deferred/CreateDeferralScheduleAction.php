<?php

namespace Jmeryar\Accounting\Actions\Deferred;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jmeryar\Accounting\Models\DeferredItem;
use Jmeryar\Accounting\Models\DeferredLine;

class CreateDeferralScheduleAction
{
    public function execute(DeferredItem $deferredItem): void
    {
        DB::transaction(function () use ($deferredItem) {
            // Clear existing draft lines to avoid duplicates if re-run
            $deferredItem->lines()->where('status', 'draft')->delete();

            $startDate = Carbon::parse($deferredItem->start_date);
            $endDate = Carbon::parse($deferredItem->end_date);

            $months = $startDate->diffInMonths($endDate) + 1; // +1 to cover the start month
            // Correction: if Jan 1 to Dec 31, that's 12 months. diffInMonths(Jan1, Dec31) is 11?
            // diffInMonths(Jan 1 2026, Jan 1 2027) is 12.
            // Jan 1 to Dec 31 is treated as 1 year.

            $totalAmount = $deferredItem->deferred_amount;

            // Safety: avoid division by zero
            if ($months < 1) {
                $months = 1;
            }

            // Calculate monthly amount (rounded)
            // Brick\Money handles allocation safely. We pass equal ratios.
            $allocation = $totalAmount->allocate(...array_fill(0, $months, 1));

            $currentDate = $startDate->copy();

            foreach ($allocation as $amount) {
                // Determine recognition date (e.g. end of month vs start)
                // Often recognition happens at end of period.
                $recognitionDate = $currentDate->copy()->endOfMonth();

                // If it's the last one, ensuring it doesn't go past end_date?
                // Standard convention: recognize at end of period.

                DeferredLine::create([
                    'company_id' => $deferredItem->company_id,
                    'deferred_item_id' => $deferredItem->id,
                    'date' => $recognitionDate,
                    'amount' => $amount,
                    'status' => 'draft',
                ]);

                $currentDate->addMonth();
            }
        });
    }
}
