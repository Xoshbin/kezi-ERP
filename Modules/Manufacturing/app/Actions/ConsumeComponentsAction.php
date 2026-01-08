<?php

namespace Modules\Manufacturing\Actions;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\DataTransferObjects\CreateStockMoveDTO;
use Modules\Inventory\DataTransferObjects\StockMoveProductLineDTO;
use Modules\Inventory\Services\Inventory\StockMoveService;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\ManufacturingOrder;

class ConsumeComponentsAction
{
    public function __construct(
        private readonly StockMoveService $stockMoveService,
    ) {}

    public function execute(ManufacturingOrder $mo): ManufacturingOrder
    {
        return DB::transaction(function () use ($mo) {
            // Validate current status
            if ($mo->status !== ManufacturingOrderStatus::InProgress) {
                throw new \InvalidArgumentException('Only in-progress manufacturing orders can consume components.');
            }

            // Create stock move for component consumption
            $productLines = [];

            foreach ($mo->lines as $line) {
                if ($line->quantity_consumed < $line->quantity_required) {
                    $quantityToConsume = $line->quantity_required - $line->quantity_consumed;

                    $productLines[] = new StockMoveProductLineDTO(
                        productId: $line->product_id,
                        quantity: $quantityToConsume,
                        unitPrice: Money::ofMinor($line->unit_cost, $line->currency_code),
                    );
                }
            }

            if (! empty($productLines)) {
                $stockMoveDTO = new CreateStockMoveDTO(
                    companyId: $mo->company_id,
                    sourceLocationId: $mo->source_location_id,
                    destinationLocationId: $mo->source_location_id, // Virtual production location (same as source for now)
                    productLines: $productLines,
                    reference: "MO/{$mo->number}",
                    scheduledDate: Carbon::now(),
                );

                $stockMove = $this->stockMoveService->createAndConfirm($stockMoveDTO);

                // Update MO lines with consumed quantities
                foreach ($mo->lines as $line) {
                    $line->update([
                        'quantity_consumed' => $line->quantity_required,
                        'stock_move_id' => $stockMove->id,
                    ]);
                }
            }

            return $mo->fresh(['lines']);
        });
    }
}
