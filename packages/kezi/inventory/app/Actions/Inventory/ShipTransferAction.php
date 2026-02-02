<?php

namespace Kezi\Inventory\Actions\Inventory;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\ShipTransferDTO;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Models\StockPicking;
use Kezi\Inventory\Services\Inventory\StockMoveService;

/**
 * Ship Transfer Action
 *
 * Moves stock from source location to transit location (Step 1 of two-step transfer).
 * This action:
 * 1. Creates stock moves from source to transit location
 * 2. Updates the picking state to 'shipped'
 * 3. Records shipped_at timestamp and shipped_by_user_id
 */
class ShipTransferAction
{
    public function __construct(
        private readonly StockMoveService $stockMoveService,
    ) {}

    public function execute(StockPicking $picking, ShipTransferDTO $dto, User $user): StockPicking
    {
        return DB::transaction(function () use ($picking, $user): StockPicking {
            $transitLocationId = $picking->transit_location_id;

            if (! $transitLocationId) {
                throw new \RuntimeException('Transfer has no transit location configured.');
            }

            // Create stock moves from source to transit for each product line
            foreach ($picking->stockMoves as $existingMove) {
                foreach ($existingMove->productLines as $productLine) {
                    /** @var \Kezi\Inventory\Models\StockMoveProductLine $productLine */
                    // Create the ship move (source -> transit)
                    $shipMoveDto = new CreateStockMoveDTO(
                        company_id: $picking->company_id,
                        move_type: StockMoveType::InternalTransfer,
                        status: StockMoveStatus::Done,
                        move_date: Carbon::now(),
                        created_by_user_id: $user->id,
                        product_lines: [
                            new CreateStockMoveProductLineDTO(
                                product_id: $productLine->product_id,
                                quantity: $productLine->quantity,
                                from_location_id: $productLine->from_location_id,
                                to_location_id: $transitLocationId,
                                description: $productLine->description,
                            ),
                        ],
                        reference: "SHIP-{$picking->reference}",
                        description: "Ship transfer to transit: {$picking->reference}",
                        source_type: StockPicking::class,
                        source_id: $picking->id,
                    );

                    $this->stockMoveService->createMove($shipMoveDto);
                }

                // Mark the original move as confirmed (waiting for receive step)
                $existingMove->update(['status' => StockMoveStatus::Confirmed]);
            }

            // Update picking state and timestamps
            $picking->update([
                'state' => StockPickingState::Shipped,
                'shipped_at' => Carbon::now(),
                'shipped_by_user_id' => $user->id,
            ]);

            /** @var StockPicking $result */
            $result = $picking->fresh(['stockMoves.productLines', 'transitLocation']);

            return $result;
        });
    }
}
