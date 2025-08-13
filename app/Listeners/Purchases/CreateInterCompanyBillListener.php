<?php

namespace App\Listeners\Purchases;

use App\Events\InvoiceConfirmed;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\InterCompanyTransactionService;

// Implementing ShouldQueue is optional but recommended for performance
class CreateInterCompanyBillListener implements ShouldQueue
{
    public function __construct(
        private readonly InterCompanyTransactionService $interCompanyService
    ) {}

    public function handle(InvoiceConfirmed $event): void
    {
        $invoice = $event->invoice;

        // Prevent circular inter-company transactions
        // If this invoice was created from an inter-company vendor bill, don't create another vendor bill
        if (str_starts_with($invoice->reference ?? '', 'IC-BILL-')) {
            return;
        }

        // Check if the customer is a related company
        $customerPartner = $invoice->customer;
        $targetCompany = $customerPartner->linkedCompany;

        if ($targetCompany && $targetCompany->id !== $invoice->company_id) {
            // This is an inter-company transaction.
            // Delegate the complex logic to our dedicated service.
            $this->interCompanyService->createVendorBillFromInvoice($invoice, $targetCompany);
        }
    }
}
