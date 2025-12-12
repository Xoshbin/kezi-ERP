<?php

namespace Modules\Sales\Actions\Sales;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Sales\DataTransferObjects\Sales\CreateDeliveryFromSalesOrderDTO;
use Modules\Sales\Enums\Sales\SalesOrderStatus;
use Modules\Sales\Models\SalesOrder;

class ConfirmSalesOrderAction
{
    public function __construct(
        protected CreateDeliveryFromSalesOrderAction $createDeliveryAction
    ) {}

    public function execute(SalesOrder $salesOrder, User $user): SalesOrder
    {
        return DB::transaction(function () use ($salesOrder, $user) {
            // Only allow confirming draft orders
            if ($salesOrder->status !== SalesOrderStatus::Draft) {
                return $salesOrder;
            }

            $salesOrder->update(['status' => SalesOrderStatus::Confirmed]);

            $dto = new CreateDeliveryFromSalesOrderDTO(
                salesOrder: $salesOrder,
                user: $user,
                scheduled_date: $salesOrder->expected_delivery_date
            );

            $this->createDeliveryAction->execute($dto);

            return $salesOrder;
        });
    }
}
