<?php

namespace Jmeryar\Accounting\Listeners\Deferred;

use Illuminate\Contracts\Queue\ShouldQueue;
use Jmeryar\Accounting\Services\DeferredItemService;
use Jmeryar\Purchase\Events\VendorBillConfirmed;

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
