<?php

namespace App\Actions\Sales;

use App\Actions\Sales\CreateInvoiceAction;
use App\DataTransferObjects\Sales\CreateInvoiceDTO;
use App\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use App\Models\VendorBill;
use App\Models\Company;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateInterCompanyInvoiceAction
{
    public function __construct(
        private readonly CreateInvoiceAction $createInvoiceAction,
        private readonly InvoiceService $invoiceService
    ) {}

    /**
     * Creates a corresponding Invoice in the partner company's books.
     */
    public function execute(VendorBill $sourceVendorBill, Company $targetCompany): void
    {
        // Ensure the vendor bill vendor corresponds to the target company partner record.
        // Add more validation as needed.

        DB::transaction(function () use ($sourceVendorBill, $targetCompany) {
            $lineDTOs = [];
            foreach ($sourceVendorBill->lines as $line) {
                $lineDTOs[] = new CreateInvoiceLineDTO(
                    description: $line->description,
                    quantity: $line->quantity,
                    unit_price: $line->unit_price, // Pass the whole Money object
                    // The income account should be derived from the product's sales settings.
                    income_account_id: $line->product->income_account_id ?? $line->product->expense_account_id,
                    product_id: $line->product_id,
                    tax_id: $line->tax_id, // This might need mapping to a sales tax
                );
            }

            // Find the partner record in the target company that represents the source company
            $customerPartner = $targetCompany->partners()
                ->where('linked_company_id', $sourceVendorBill->company_id)
                ->firstOrFail();

            $invoiceDTO = new CreateInvoiceDTO(
                company_id: $targetCompany->id,
                customer_id: $customerPartner->id,
                currency_id: $sourceVendorBill->currency_id,
                invoice_date: $sourceVendorBill->bill_date,
                due_date: $sourceVendorBill->due_date,
                lines: $lineDTOs,
                fiscal_position_id: null,
                reference: "IC-BILL-{$sourceVendorBill->id}", // This provides the audit trail
            );

            // Use our existing, tested Action to create the invoice in the target company
            $invoice = $this->createInvoiceAction->execute($invoiceDTO);

            // Post the invoice to create the journal entry and maintain consistency
            $user = Auth::user();
            $this->invoiceService->confirm($invoice, $user);
        });
    }
}
