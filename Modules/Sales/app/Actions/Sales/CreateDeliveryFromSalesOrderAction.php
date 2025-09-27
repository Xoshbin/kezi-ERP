<?php

namespace Modules\Sales\Actions\Sales;

use App\Actions\Inventory\CreateStockMoveAction;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use App\DataTransferObjects\Sales\CreateDeliveryFromSalesOrderDTO;
use App\Enums\Inventory\StockLocationType;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Inventory\StockPickingState;
use App\Enums\Inventory\StockPickingType;
use App\Enums\Products\ProductType;
use App\Events\Inventory\StockMoveConfirmed;
use App\Models\SalesOrder;
use App\Models\StockLocation;
use App\Models\StockPicking;
use App\Models\User;
use App\Services\Inventory\StockReservationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Action for creating delivery orders from sales orders
 */
class CreateDeliveryFromSalesOrderAction
{
    public function __construct(
        protected CreateStockMoveAction $createStockMoveAction
    ) {}

    /**
     * Create delivery orders for all deliverable products in a sales order
     *
     * @return Collection<int, \App\Models\StockMove> Collection of created stock moves
     */
    public function execute(CreateDeliveryFromSalesOrderDTO $dto): Collection
    {
        $salesOrder = $dto->salesOrder;
        $user = $dto->user;

        // Validate that the sales order can deliver goods
        if (!$salesOrder->canDeliverGoods()) {
            throw ValidationException::withMessages([
                'sales_order' => 'This sales order cannot deliver goods in its current status.',
            ]);
        }

        return DB::transaction(function () use ($dto, $salesOrder, $user) {
            $stockMoves = collect();

            // Get stock locations with fallback strategy
            $locations = $this->getStockLocations($salesOrder);

            if (!$locations['warehouse'] || !$locations['customer']) {
                // Skip stock move creation if locations are not available
                return $stockMoves;
            }

            // Create a picking for this delivery
            $picking = StockPicking::create([
                'company_id' => $salesOrder->company_id,
                'type' => StockPickingType::Outgoing,
                'state' => StockPickingState::Draft,
                'partner_id' => $salesOrder->customer_id,
                'scheduled_date' => $dto->scheduled_date ?? now(),
                'reference' => "OUT/{$salesOrder->so_number}",
                'origin' => "Sales Order: {$salesOrder->so_number}",
                'created_by_user_id' => $user->id,
            ]);

            $reservationService = app(StockReservationService::class);

            foreach ($salesOrder->lines as $line) {
                if ($line->product && $line->product->type === ProductType::Storable) {
                    // Only create delivery for quantities that haven't been delivered yet
                    $remainingQuantity = $line->getRemainingToDeliver();
                    
                    if ($remainingQuantity > 0) {
                        $stockMove = $this->createStockMoveForLine(
                            $salesOrder,
                            $line,
                            $user,
                            $locations['warehouse'],
                            $locations['customer'],
                            $remainingQuantity
                        );

                        // Attach to picking
                        $stockMove->update(['picking_id' => $picking->getKey()]);

                        // Reserve as much as possible from warehouse before processing the move
                        $reservationService->reserveForMove($stockMove, $locations['warehouse']->id);

                        $stockMoves->push($stockMove);

                        // Update the sales order line's delivered quantity
                        $line->updateDeliveredQuantity($line->quantity_delivered + $remainingQuantity);

                        // If we're in automatic mode, mark the move as done and dispatch event
                        if ($dto->autoConfirm) {
                            $stockMove->update(['status' => StockMoveStatus::Done]);
                            StockMoveConfirmed::dispatch($stockMove);
                        }
                    }
                }
            }

            // Update sales order status based on delivery progress
            $this->updateSalesOrderStatus($salesOrder);

            return $stockMoves;
        });
    }

    /**
     * Get stock locations for the delivery
     */
    private function getStockLocations(SalesOrder $salesOrder): array
    {
        $company = $salesOrder->company;

        // Try to get warehouse location from sales order delivery location
        $warehouseLocation = $salesOrder->deliveryLocation;
        
        // Fallback to company's main warehouse
        if (!$warehouseLocation) {
            $warehouseLocation = StockLocation::where('company_id', $company->id)
                ->where('location_type', StockLocationType::Internal)
                ->first();
        }

        // Get customer location (create if doesn't exist)
        $customerLocation = StockLocation::firstOrCreate([
            'company_id' => $company->id,
            'location_type' => StockLocationType::Customer,
            'partner_id' => $salesOrder->customer_id,
        ], [
            'name' => "Customer: {$salesOrder->customer->name}",
            'is_active' => true,
        ]);

        return [
            'warehouse' => $warehouseLocation,
            'customer' => $customerLocation,
        ];
    }

    /**
     * Create a stock move for a specific sales order line
     */
    private function createStockMoveForLine(
        SalesOrder $salesOrder,
        $line,
        User $user,
        StockLocation $sourceLocation,
        StockLocation $destinationLocation,
        float $quantity
    ) {
        $productLineDto = new CreateStockMoveProductLineDTO(
            product_id: $line->product_id,
            quantity: $quantity,
            source_location_id: $sourceLocation->id,
            destination_location_id: $destinationLocation->id,
            unit_cost: null, // Will be determined by cost layers
        );

        $dto = new CreateStockMoveDTO(
            company_id: $salesOrder->company_id,
            product_lines: [$productLineDto],
            move_type: StockMoveType::Outgoing,
            status: StockMoveStatus::Draft,
            move_date: now(),
            reference: $salesOrder->so_number,
            description: "Delivery for sales order {$salesOrder->so_number}",
            source_id: $salesOrder->id,
            source_type: SalesOrder::class,
            created_by_user_id: $user->id,
        );

        return $this->createStockMoveAction->execute($dto);
    }

    /**
     * Update the sales order status based on delivery progress
     */
    private function updateSalesOrderStatus(SalesOrder $salesOrder): void
    {
        $salesOrder->refresh();
        
        if ($salesOrder->isFullyDelivered()) {
            if ($salesOrder->isFullyInvoiced()) {
                $salesOrder->status = \App\Enums\Sales\SalesOrderStatus::Done;
            } else {
                $salesOrder->status = \App\Enums\Sales\SalesOrderStatus::FullyDelivered;
            }
        } else {
            $salesOrder->status = \App\Enums\Sales\SalesOrderStatus::PartiallyDelivered;
        }
        
        $salesOrder->save();
    }
}
