<?php

namespace App\Actions\Sales;

use App\Actions\Purchases\CreateVendorBillAction;
use App\Actions\Sales\CreateInvoiceAction;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\DataTransferObjects\Sales\CreateInvoiceDTO;
use App\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use App\DataTransferObjects\Sales\CreateRecurringInvoiceDTO;
use App\DataTransferObjects\Sales\RecurringInvoiceResultDTO;
use App\Models\Company;
use App\Models\Partner;
use App\Models\RecurringInvoiceTemplate;
use App\Models\User;
use App\Services\Accounting\LockDateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateRecurringInterCompanyInvoiceAction
{
    public function __construct(
        protected CreateInvoiceAction $createInvoiceAction,
        protected CreateVendorBillAction $createVendorBillAction,
        protected LockDateService $lockDateService,
    ) {}

    /**
     * Execute the creation of recurring inter-company invoice and corresponding vendor bill.
     */
    public function execute(CreateRecurringInvoiceDTO $dto, User $user): RecurringInvoiceResultDTO
    {
        return DB::transaction(function () use ($dto, $user) {
            // Validate the inter-company relationship
            $this->validateInterCompanyRelationship($dto);

            // Enforce lock dates for both companies
            $sourceCompany = Company::findOrFail($dto->company_id);
            $targetCompany = Company::findOrFail($dto->target_company_id);
            
            $this->lockDateService->enforce($sourceCompany, $dto->invoice_date);
            $this->lockDateService->enforce($targetCompany, $dto->invoice_date);

            // Create the invoice in the source company
            $invoice = $this->createInvoice($dto, $sourceCompany, $targetCompany, $user);

            // Create the corresponding vendor bill in the target company
            $vendorBill = $this->createVendorBill($dto, $sourceCompany, $targetCompany, $invoice, $user);

            // Update the recurring template
            $template = RecurringInvoiceTemplate::findOrFail($dto->recurring_template_id);
            $template->updateAfterGeneration();

            Log::info("Created recurring inter-company invoice {$invoice->id} and vendor bill {$vendorBill->id} from template {$template->id}");

            return new RecurringInvoiceResultDTO(
                invoice: $invoice,
                vendorBill: $vendorBill,
                reference: $dto->reference,
                success: true,
            );
        });
    }

    /**
     * Create the invoice in the source company.
     */
    protected function createInvoice(
        CreateRecurringInvoiceDTO $dto,
        Company $sourceCompany,
        Company $targetCompany,
        User $user
    ) {
        // Find the partner representing the target company in source company's books
        $customerPartner = Partner::where('company_id', $sourceCompany->id)
            ->where('linked_company_id', $targetCompany->id)
            ->firstOrFail();

        // Convert recurring invoice lines to invoice lines
        $invoiceLines = array_map(function ($line) use ($dto) {
            return new CreateInvoiceLineDTO(
                description: $line->description,
                quantity: $line->quantity,
                unit_price: $line->unit_price,
                income_account_id: $dto->income_account_id,
                product_id: $line->product_id,
                tax_id: $line->tax_id,
            );
        }, $dto->lines);

        $invoiceDTO = new CreateInvoiceDTO(
            company_id: $sourceCompany->id,
            customer_id: $customerPartner->id,
            currency_id: $dto->currency_id,
            invoice_date: $dto->invoice_date->format('Y-m-d'),
            due_date: $dto->due_date->format('Y-m-d'),
            lines: $invoiceLines,
            fiscal_position_id: null,
            reference: $dto->reference,
        );

        $invoice = $this->createInvoiceAction->execute($invoiceDTO);

        // Link to recurring template
        $invoice->update(['recurring_template_id' => $dto->recurring_template_id]);

        return $invoice;
    }

    /**
     * Create the corresponding vendor bill in the target company.
     */
    protected function createVendorBill(
        CreateRecurringInvoiceDTO $dto,
        Company $sourceCompany,
        Company $targetCompany,
        $invoice,
        User $user
    ) {
        // Find the partner representing the source company in target company's books
        $vendorPartner = Partner::where('company_id', $targetCompany->id)
            ->where('linked_company_id', $sourceCompany->id)
            ->firstOrFail();

        // Convert recurring invoice lines to vendor bill lines
        $vendorBillLines = array_map(function ($line) use ($dto) {
            return new CreateVendorBillLineDTO(
                product_id: $line->product_id,
                description: $line->description,
                quantity: $line->quantity,
                unit_price: $line->unit_price,
                expense_account_id: $dto->expense_account_id,
                tax_id: $line->tax_id,
                analytic_account_id: null,
            );
        }, $dto->lines);

        $vendorBillDTO = new CreateVendorBillDTO(
            company_id: $targetCompany->id,
            vendor_id: $vendorPartner->id,
            currency_id: $dto->currency_id,
            bill_reference: "IC-RECURRING-BILL-{$invoice->id}",
            bill_date: $dto->invoice_date->format('Y-m-d'),
            accounting_date: $dto->invoice_date->format('Y-m-d'),
            due_date: $dto->due_date->format('Y-m-d'),
            lines: $vendorBillLines,
            created_by_user_id: $user->id,
        );

        $vendorBill = $this->createVendorBillAction->execute($vendorBillDTO);

        // Link to recurring template
        $vendorBill->update(['recurring_template_id' => $dto->recurring_template_id]);

        return $vendorBill;
    }

    /**
     * Validate the inter-company relationship exists.
     */
    protected function validateInterCompanyRelationship(CreateRecurringInvoiceDTO $dto): void
    {
        if ($dto->company_id === $dto->target_company_id) {
            throw new \InvalidArgumentException('Source and target companies must be different');
        }

        // Validate partner relationships exist in both directions
        $sourceToTarget = Partner::where('company_id', $dto->company_id)
            ->where('linked_company_id', $dto->target_company_id)
            ->exists();

        $targetToSource = Partner::where('company_id', $dto->target_company_id)
            ->where('linked_company_id', $dto->company_id)
            ->exists();

        if (!$sourceToTarget || !$targetToSource) {
            throw new \InvalidArgumentException(
                "Missing partner relationships between companies {$dto->company_id} and {$dto->target_company_id}"
            );
        }
    }
}
