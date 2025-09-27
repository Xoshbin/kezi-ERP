<?php

namespace Modules\Purchase\Actions\Purchases;

use App\DataTransferObjects\Purchases\CreatePurchaseOrderDTO;
use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Services\Accounting\LockDateService;
use Illuminate\Support\Facades\DB;

/**
 * Action for creating a new Purchase Order
 */
class CreatePurchaseOrderAction
{
    public function __construct(
        protected LockDateService $lockDateService,
        protected CreatePurchaseOrderLineAction $createLineAction
    ) {}

    /**
     * Execute the action to create a purchase order
     */
    public function execute(CreatePurchaseOrderDTO $dto): PurchaseOrder
    {
        $this->lockDateService->enforce(
            Company::findOrFail($dto->company_id), 
            $dto->po_date
        );

        return DB::transaction(function () use ($dto) {
            // Create the purchase order
            $purchaseOrder = PurchaseOrder::create([
                'company_id' => $dto->company_id,
                'vendor_id' => $dto->vendor_id,
                'currency_id' => $dto->currency_id,
                'created_by_user_id' => $dto->created_by_user_id,
                'reference' => $dto->reference,
                'po_date' => $dto->po_date,
                'expected_delivery_date' => $dto->expected_delivery_date,
                'exchange_rate_at_creation' => $dto->exchange_rate_at_creation,
                'notes' => $dto->notes,
                'terms_and_conditions' => $dto->terms_and_conditions,
                'delivery_location_id' => $dto->delivery_location_id,
                'total_amount' => 0,
                'total_tax' => 0,
            ]);

            // Create the purchase order lines
            foreach ($dto->lines as $lineDto) {
                $this->createLineAction->execute($purchaseOrder, $lineDto);
            }

            // Refresh to get the calculated totals
            $purchaseOrder->refresh();
            $purchaseOrder->load('lines');

            return $purchaseOrder;
        });
    }
}
