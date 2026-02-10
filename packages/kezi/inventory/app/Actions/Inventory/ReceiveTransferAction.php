<?php

namespace Kezi\Inventory\Actions\Inventory;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\ReceiveTransferDTO;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Models\StockPicking;
use Kezi\Inventory\Services\Inventory\StockMoveService;
use Kezi\Inventory\Services\Inventory\StockReservationService;

/**
 * Receive Transfer Action
 *
 * Moves stock from transit location to destination location (Step 2 of two-step transfer).
 * This action:
 * 1. Creates stock moves from transit to destination location
 * 2. Updates the picking state to 'done'
 * 3. Records received_at timestamp and received_by_user_id
 * 4. Releases any stock reservations
 */
class ReceiveTransferAction
{
    public function __construct(
        private readonly StockMoveService $stockMoveService,
        private readonly StockReservationService $reservationService,
    ) {}

    public function execute(StockPicking $picking, ReceiveTransferDTO $dto, User $user): StockPicking
    {
        return DB::transaction(function () use ($picking, $user): StockPicking {
            $transitLocationId = $picking->transit_location_id;
            $destinationLocationId = $picking->destination_location_id;

            if (! $transitLocationId || ! $destinationLocationId) {
                throw new \RuntimeException('Transfer has no transit or destination location configured.');
            }

            // Create stock moves from transit to destination for each product line
            foreach ($picking->stockMoves as $existingMove) {
                foreach ($existingMove->productLines as $productLine) {
                    /** @var \Kezi\Inventory\Models\StockMoveProductLine $productLine */
                    // Create the receive move (transit -> destination)
                    $receiveMoveDto = new CreateStockMoveDTO(
                        company_id: $picking->company_id,
                        move_type: StockMoveType::InternalTransfer,
                        status: StockMoveStatus::Done,
                        move_date: Carbon::now(),
                        created_by_user_id: $user->id,
                        product_lines: [
                            new CreateStockMoveProductLineDTO(
                                product_id: $productLine->product_id,
                                quantity: $productLine->quantity,
                                from_location_id: $transitLocationId,
                                to_location_id: $destinationLocationId,
                                description: $productLine->description,
                            ),
                        ],
                        reference: "RECV-{$picking->reference}",
                        description: "Receive transfer from transit: {$picking->reference}",
                        source_type: StockPicking::class,
                        source_id: $picking->id,
                    );

                    $this->stockMoveService->createMove($receiveMoveDto);
                }

                // Mark the original move as done quietly to avoid re-triggering quant updates
                // (The stock movement is already handled by the receiveMove above)
                $existingMove->status = StockMoveStatus::Done;
                $existingMove->saveQuietly();
            }

            // Update picking state and timestamps
            $picking->update([
                'state' => StockPickingState::Done,
                'received_at' => Carbon::now(),
                'received_by_user_id' => $user->id,
                'completed_at' => Carbon::now(),
            ]);

            // Release stock reservations
            foreach ($picking->stockMoves as $existingMove) {
                $this->reservationService->releaseForMove($existingMove);
            }

            /** @var StockPicking $result */
            $result = $picking->fresh(['stockMoves.productLines', 'destinationLocation']);

            return $result;
        });
    }
}
