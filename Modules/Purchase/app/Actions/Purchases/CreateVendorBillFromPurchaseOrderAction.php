<?php

namespace Modules\Purchase\Actions\Purchases;

use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\PurchaseOrder;
use Illuminate\Validation\ValidationException;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillDTO;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillFromPurchaseOrderDTO;

class CreateVendorBillFromPurchaseOrderAction
{
    public function __construct(
        protected CreateVendorBillAction $createVendorBillAction,
    ) {}

    public function execute(CreateVendorBillFromPurchaseOrderDTO $dto): VendorBill
    {
        $purchaseOrder = $this->validateAndGetPurchaseOrder($dto->purchase_order_id);

        // Transform PO data to VendorBill DTO
        $vendorBillDTO = $this->transformPurchaseOrderToVendorBillDTO($purchaseOrder, $dto);

        // Create the vendor bill using the existing action
        $vendorBill = $this->createVendorBillAction->execute($vendorBillDTO);

        // Update the purchase order status based on billing progress
        $purchaseOrder->refresh(); // Refresh to get latest data
        $purchaseOrder->updateStatusBasedOnBilling();

        return $vendorBill;
    }

    private function validateAndGetPurchaseOrder(int $purchaseOrderId): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::with(['lines.product', 'vendor', 'currency'])->find($purchaseOrderId);

        if (!$purchaseOrder) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'The selected purchase order does not exist.',
            ]);
        }

        if (!$purchaseOrder->status->canCreateBill()) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'The purchase order status does not allow creating bills.',
            ]);
        }

        return $purchaseOrder;
    }

    private function transformPurchaseOrderToVendorBillDTO(
        PurchaseOrder $purchaseOrder,
        CreateVendorBillFromPurchaseOrderDTO $dto,
    ): CreateVendorBillDTO {
        // Transform PO lines to VendorBill lines
        $vendorBillLines = [];

        foreach ($purchaseOrder->lines as $poLine) {
            // Use custom quantity if provided, otherwise use PO quantity
            $quantity = $dto->line_quantities[$poLine->id] ?? $poLine->quantity;

            // Skip lines with zero quantity
            if ($quantity <= 0) {
                continue;
            }

            // Determine expense account - use product's account or require it to be set
            $expenseAccountId = null;
            if ($poLine->product_id && $poLine->product) {
                $expenseAccountId = $poLine->product->expense_account_id;
            }

            if (!$expenseAccountId) {
                throw ValidationException::withMessages([
                    'lines' => "Product '{$poLine->description}' must have an expense account configured.",
                ]);
            }

            $vendorBillLines[] = new CreateVendorBillLineDTO(
                product_id: $poLine->product_id,
                description: $poLine->description,
                quantity: $quantity,
                unit_price: $poLine->unit_price,
                expense_account_id: $expenseAccountId,
                tax_id: $poLine->tax_id,
                analytic_account_id: null, // Could be enhanced to copy from PO if needed
                currency: $purchaseOrder->currency->code
            );
        }

        if (empty($vendorBillLines)) {
            throw ValidationException::withMessages([
                'lines' => 'No valid lines found to create vendor bill.',
            ]);
        }

        return new CreateVendorBillDTO(
            company_id: $purchaseOrder->company_id,
            vendor_id: $purchaseOrder->vendor_id,
            currency_id: $purchaseOrder->currency_id,
            bill_reference: $dto->bill_reference,
            bill_date: $dto->bill_date,
            accounting_date: $dto->accounting_date,
            due_date: $dto->due_date,
            lines: $vendorBillLines,
            created_by_user_id: $dto->created_by_user_id,
            payment_term_id: $dto->payment_term_id,
            purchase_order_id: $purchaseOrder->id
        );
    }
}
