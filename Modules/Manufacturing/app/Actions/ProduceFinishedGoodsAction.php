<?php

namespace Modules\Manufacturing\Actions;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\DataTransferObjects\CreateStockMoveDTO;
use Modules\Inventory\DataTransferObjects\StockMoveProductLineDTO;
use Modules\Inventory\Services\StockMoveService;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\ManufacturingOrder;

class ProduceFinishedGoodsAction
{
    public function __construct(
        private readonly StockMoveService $stockMoveService,
    ) {}

    public function execute(ManufacturingOrder $mo): ManufacturingOrder
    {
        return DB::transaction(function () use ($mo) {
            // Validate current status
            if ($mo->status !== ManufacturingOrderStatus::InProgress) {
                throw new \InvalidArgumentException('Only in-progress manufacturing orders can produce finished goods.');
            }

            // Calculate actual production cost (sum of consumed components)
            $totalCost = Money::zero($mo->lines->first()->currency_code);

            foreach ($mo->lines as $line) {
                $lineCost = Money::ofMinor($line->unit_cost, $line->currency_code)
                    ->multipliedBy($line->quantity_consumed);
                $totalCost = $totalCost->plus($lineCost);
            }

            // Calculate unit cost for finished product
            $unitCost = $totalCost->dividedBy($mo->quantity_to_produce, roundingMode: \Brick\Math\RoundingMode::HALF_UP);

            // Create stock move for finished goods receipt
            $stockMoveDTO = new CreateStockMoveDTO(
                companyId: $mo->company_id,
                sourceLocationId: $mo->source_location_id, // From production (virtual)
                destinationLocationId: $mo->destination_location_id, // To finished goods warehouse
                productLines: [
                    new StockMoveProductLineDTO(
                        productId: $mo->product_id,
                        quantity: $mo->quantity_to_produce,
                        unitPrice: $unitCost,
                    ),
                ],
                reference: "MO/{$mo->number}",
                scheduledDate: Carbon::now(),
            );

            $stockMove = $this->stockMoveService->createAndConfirm($stockMoveDTO);

            // Update MO status
            $mo->update([
                'status' => ManufacturingOrderStatus::Done,
                'quantity_produced' => $mo->quantity_to_produce,
                'actual_end_date' => Carbon::now(),
            ]);

            // Complete work orders
            $mo->workOrders()->where('status', '!=', 'done')->update([
                'status' => 'done',
                'completed_at' => Carbon::now(),
            ]);

            return $mo->fresh();
        });
    }
}
