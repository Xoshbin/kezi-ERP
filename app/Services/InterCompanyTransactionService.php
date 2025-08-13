<?php

namespace App\Services;

use App\Actions\Purchases\CreateVendorBillAction;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\Models\Invoice;
use App\Models\Company;
use App\Services\VendorBillService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InterCompanyTransactionService
{
    public function __construct(
        private readonly CreateVendorBillAction $createVendorBillAction,
        private readonly VendorBillService $vendorBillService
    ) {}

    /**
     * Creates a corresponding Vendor Bill in the partner company's books.
     */
    public function createVendorBillFromInvoice(Invoice $sourceInvoice, Company $targetCompany): void
    {
        // Ensure the invoice customer corresponds to the target company partner record.
        // Add more validation as needed.

        DB::transaction(function () use ($sourceInvoice, $targetCompany) {
            $lineDTOs = [];
            foreach ($sourceInvoice->invoiceLines as $line) {
                $lineDTOs[] = new CreateVendorBillLineDTO(
                    product_id: $line->product_id,
                    description: $line->description,
                    quantity: $line->quantity,
                    unit_price: $line->unit_price, // Pass the whole Money object
                    // The expense account should be derived from the product's purchase settings.
                    expense_account_id: $line->product->expense_account_id,
                    tax_id: $line->tax_id, // This might need mapping to a purchase tax
                    analytic_account_id: null // Not available from invoice context
                );
            }

            // Find the partner record in the target company that represents the source company
            $vendorPartner = $targetCompany->partners()
                ->where('linked_company_id', $sourceInvoice->company_id)
                ->firstOrFail();

            $vendorBillDTO = new CreateVendorBillDTO(
                company_id: $targetCompany->id,
                vendor_id: $vendorPartner->id,
                currency_id: $sourceInvoice->currency_id,
                bill_reference: "IC-INV-{$sourceInvoice->id}", // This provides the audit trail
                bill_date: $sourceInvoice->invoice_date,
                accounting_date: $sourceInvoice->invoice_date, // Use invoice_date as accounting_date
                due_date: $sourceInvoice->due_date,
                lines: $lineDTOs,
                created_by_user_id: Auth::id(),
            );

            // Use our existing, tested Action to create the bill in the target company
            $vendorBill = $this->createVendorBillAction->execute($vendorBillDTO);

            // Post the vendor bill to create the journal entry and maintain consistency
            $user = Auth::user();
            $this->vendorBillService->post($vendorBill, $user);
        });
    }
}
