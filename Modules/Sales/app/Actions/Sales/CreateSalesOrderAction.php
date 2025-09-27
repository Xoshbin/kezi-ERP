<?php

namespace Modules\Sales\Actions\Sales;

use App\DataTransferObjects\Sales\CreateSalesOrderDTO;
use App\Models\Company;
use App\Models\SalesOrder;
use App\Services\Accounting\LockDateService;
use Illuminate\Support\Facades\DB;

/**
 * Action for creating a new Sales Order
 */
class CreateSalesOrderAction
{
    public function __construct(
        protected \Modules\Accounting\Services\Accounting\LockDateService $lockDateService,
        protected CreateSalesOrderLineAction $createLineAction
    ) {}

    /**
     * Execute the action to create a sales order
     */
    public function execute(CreateSalesOrderDTO $dto): SalesOrder
    {
        $this->lockDateService->enforce(
            Company::findOrFail($dto->company_id), 
            $dto->so_date
        );

        return DB::transaction(function () use ($dto) {
            // Create the sales order
            $salesOrder = SalesOrder::create([
                'company_id' => $dto->company_id,
                'customer_id' => $dto->customer_id,
                'currency_id' => $dto->currency_id,
                'created_by_user_id' => $dto->created_by_user_id,
                'reference' => $dto->reference,
                'so_date' => $dto->so_date,
                'expected_delivery_date' => $dto->expected_delivery_date,
                'exchange_rate_at_creation' => $dto->exchange_rate_at_creation,
                'notes' => $dto->notes,
                'terms_and_conditions' => $dto->terms_and_conditions,
                'delivery_location_id' => $dto->delivery_location_id,
                'total_amount' => 0,
                'total_tax' => 0,
            ]);

            // Create the sales order lines
            foreach ($dto->lines as $lineDto) {
                $this->createLineAction->execute($salesOrder, $lineDto);
            }

            // Calculate and update totals
            $salesOrder->refresh();
            $salesOrder->calculateTotals();
            $salesOrder->save();

            return $salesOrder;
        });
    }
}
