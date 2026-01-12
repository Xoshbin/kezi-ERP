<?php

namespace Modules\Accounting\Listeners\Deferred;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Accounting\Services\DeferredItemService;
use Modules\Purchase\Events\VendorBillConfirmed;

class CreateDeferredExpenseFromVendorBill implements ShouldQueue
{
    public function __construct(
        protected DeferredItemService $deferredItemService
    ) {}

    public function handle(VendorBillConfirmed $event): void
    {
        $vendorBill = $event->vendorBill;

        foreach ($vendorBill->lines as $line) {
            $this->deferredItemService->createFromVendorBillLine($line);
        }
    }
}
