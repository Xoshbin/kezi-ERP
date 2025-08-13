<?php

namespace App\Services;

use App\Actions\Purchases\CreateVendorBillAction;
use App\Actions\Sales\CreateInvoiceAction;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\DataTransferObjects\Sales\CreateInvoiceDTO;
use App\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use App\Models\Invoice;
use App\Models\VendorBill;
use App\Models\Company;
use App\Services\VendorBillService;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InterCompanyTransactionService
{
    public function __construct(
        private readonly CreateVendorBillAction $createVendorBillAction,
        private readonly CreateInvoiceAction $createInvoiceAction,
        private readonly VendorBillService $vendorBillService,
        private readonly InvoiceService $invoiceService
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

    /**
     * Creates a corresponding Invoice in the partner company's books.
     */
    public function createInvoiceFromVendorBill(VendorBill $sourceVendorBill, Company $targetCompany): void
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
