<?php

namespace Modules\Purchase\Actions\Purchases;

use App\Models\Company;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillDTO;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\VendorBill;

class CreateVendorBillAction
{
    public function __construct(
        protected \Modules\Accounting\Services\Accounting\LockDateService $lockDateService,
        protected CreateVendorBillLineAction $createVendorBillLineAction,
    ) {}

    public function execute(CreateVendorBillDTO $createVendorBillDTO): VendorBill
    {
        $this->lockDateService->enforce(Company::findOrFail($createVendorBillDTO->company_id), Carbon::parse($createVendorBillDTO->bill_date));

        // Validate purchase order if provided
        if ($createVendorBillDTO->purchase_order_id) {
            $this->validatePurchaseOrder($createVendorBillDTO);
        }

        return DB::transaction(function () use ($createVendorBillDTO) {
            $vendorBill = VendorBill::create([
                'company_id' => $createVendorBillDTO->company_id,
                'vendor_id' => $createVendorBillDTO->vendor_id,
                'currency_id' => $createVendorBillDTO->currency_id,
                'purchase_order_id' => $createVendorBillDTO->purchase_order_id,
                'bill_reference' => $createVendorBillDTO->bill_reference,
                'bill_date' => $createVendorBillDTO->bill_date,
                'accounting_date' => $createVendorBillDTO->accounting_date,
                'due_date' => $createVendorBillDTO->due_date,
                'payment_term_id' => $createVendorBillDTO->payment_term_id,
                'incoterm' => $createVendorBillDTO->incoterm,
                // Add default zero values to satisfy NOT NULL constraints.
                // The VendorBillLineObserver will update these as lines are added.
                'total_tax' => 0,
                'total_amount' => 0,
            ]);

            foreach ($createVendorBillDTO->lines as $lineDTO) {
                $this->createVendorBillLineAction->execute($vendorBill, $lineDTO);
            }

            // The VendorBillLineObserver will handle recalculating totals.
            // We just need to reload the relationship to get the fresh data.
            $freshVendorBill = $vendorBill->fresh('lines');
            if (! $freshVendorBill) {
                throw new Exception('Failed to refresh vendor bill after creation');
            }

            return $freshVendorBill;
        });
    }

    /**
     * Validate that the purchase order is compatible with the vendor bill.
     */
    private function validatePurchaseOrder(CreateVendorBillDTO $createVendorBillDTO): void
    {
        $purchaseOrder = PurchaseOrder::find($createVendorBillDTO->purchase_order_id);

        if (! $purchaseOrder) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'The selected purchase order does not exist.',
            ]);
        }

        // Validate that the purchase order belongs to the same company
        if ($purchaseOrder->company_id !== $createVendorBillDTO->company_id) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'The purchase order does not belong to the same company.',
            ]);
        }

        // Validate that the vendor matches
        if ($purchaseOrder->vendor_id !== $createVendorBillDTO->vendor_id) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'The purchase order vendor does not match the bill vendor.',
            ]);
        }

        // Validate that the currency matches
        if ($purchaseOrder->currency_id !== $createVendorBillDTO->currency_id) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'The purchase order currency does not match the bill currency.',
            ]);
        }

        // Validate that the purchase order status allows billing
        if (! $purchaseOrder->status->canCreateBill()) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'The purchase order status does not allow creating bills.',
            ]);
        }
    }
}
