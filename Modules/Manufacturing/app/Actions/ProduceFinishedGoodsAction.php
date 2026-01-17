<?php

namespace Modules\Manufacturing\Actions;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Modules\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Services\Inventory\StockMoveService;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\ManufacturingOrder;

class ProduceFinishedGoodsAction
{
    public function __construct(
        private readonly StockMoveService $stockMoveService,
    ) {}

    public function execute(ManufacturingOrder $mo): ManufacturingOrder
    {
        /** @var ManufacturingOrder */
        return DB::transaction(function () use ($mo) {
            // Validate current status
            if ($mo->status !== ManufacturingOrderStatus::InProgress) {
                throw new \InvalidArgumentException('Only in-progress manufacturing orders can produce finished goods.');
            }

            // Calculate actual production cost (sum of consumed components)
            $firstLine = $mo->lines->first();
            if (! $firstLine) {
                // Should not happen for a valid MO, but safe to handle
                throw new \RuntimeException("Manufacturing Order {$mo->number} has no lines to process.");
            }
            $totalCost = Money::zero($firstLine->currency_code);

            foreach ($mo->lines as $line) {
                // unit_cost is cast to Money via BaseCurrencyMoneyCast
                /** @var Money $unitCost */
                $unitCost = $line->unit_cost;
                $lineCost = $unitCost->multipliedBy($line->quantity_consumed);
                $totalCost = $totalCost->plus($lineCost);
            }

            // Calculate unit cost for finished product
            $unitCost = $totalCost->dividedBy($mo->quantity_to_produce, roundingMode: \Brick\Math\RoundingMode::HALF_UP);

            // Create stock move for finished goods receipt
            $stockMoveDTO = new CreateStockMoveDTO(
                company_id: $mo->company_id,
                move_type: StockMoveType::Incoming, // Production output is a receipt into stock
                status: StockMoveStatus::Done,
                move_date: Carbon::now(),
                created_by_user_id: (int) (auth()->id() ?? 1), // Fallback for testing/console
                product_lines: [
                    new CreateStockMoveProductLineDTO(
                        product_id: $mo->product_id,
                        quantity: $mo->quantity_to_produce,
                        from_location_id: $mo->source_location_id, // Virtual production location
                        to_location_id: $mo->destination_location_id, // Finished goods warehouse
                        description: "Production of MO/{$mo->number}",
                        source_type: ManufacturingOrder::class,
                        source_id: $mo->id
                    ),
                ],
                reference: "MO/{$mo->number}",
                source_type: ManufacturingOrder::class,
                source_id: $mo->id
            );

            // Use createMove which handles auto-confirmation if status is Done
            $stockMove = $this->stockMoveService->createMove($stockMoveDTO);

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
