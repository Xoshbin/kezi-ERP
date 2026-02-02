<?php

namespace Kezi\Sales\Actions\Sales;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Kezi\Foundation\Services\SequenceService;
use Kezi\Sales\DataTransferObjects\Sales\CreateDeliveryFromSalesOrderDTO;
use Kezi\Sales\Enums\Sales\SalesOrderStatus;
use Kezi\Sales\Models\SalesOrder;

class ConfirmSalesOrderAction
{
    public function __construct(
        protected CreateDeliveryFromSalesOrderAction $createDeliveryAction,
        protected SequenceService $sequenceService,
    ) {}

    public function execute(SalesOrder $salesOrder, User $user): SalesOrder
    {
        return DB::transaction(function () use ($salesOrder, $user) {
            // Only allow confirming draft orders
            if ($salesOrder->status !== SalesOrderStatus::Draft) {
                return $salesOrder;
            }

            $salesOrder->update([
                'status' => SalesOrderStatus::Confirmed,
                'so_number' => $this->sequenceService->getNextNumber(
                    company: $salesOrder->company,
                    documentType: 'sales_order',
                    prefix: 'SO',
                    padding: 7,
                ),
                'confirmed_at' => now(),
            ]);

            $dto = new CreateDeliveryFromSalesOrderDTO(
                salesOrder: $salesOrder,
                user: $user,
                scheduled_date: $salesOrder->expected_delivery_date,
                autoConfirm: $salesOrder->company->inventory_accounting_mode->autoRecordsInventory(),
            );

            $this->createDeliveryAction->execute($dto);

            return $salesOrder;
        });
    }
}
