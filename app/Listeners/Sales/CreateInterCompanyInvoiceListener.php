<?php

namespace App\Listeners\Sales;

use App\Events\VendorBillConfirmed;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Actions\Sales\CreateInterCompanyInvoiceAction;

class CreateInterCompanyInvoiceListener implements ShouldQueue
{
    public function __construct(
        private readonly CreateInterCompanyInvoiceAction $createInterCompanyInvoiceAction
    ) {}

    public function handle(VendorBillConfirmed $event): void
    {
        $vendorBill = $event->vendorBill;

        // Prevent circular inter-company transactions
        // If this vendor bill was created from an inter-company invoice, don't create another invoice
        if (str_starts_with($vendorBill->bill_reference ?? '', 'IC-INV-')) {
            return;
        }

        // Check if the vendor is a related company
        $vendorPartner = $vendorBill->vendor;
        $targetCompany = $vendorPartner->linkedCompany;

        if ($targetCompany && $targetCompany->id !== $vendorBill->company_id) {
            // This is an inter-company transaction.
            // Delegate the complex logic to our dedicated action.
            $this->createInterCompanyInvoiceAction->execute($vendorBill, $targetCompany);
        }
    }
}
