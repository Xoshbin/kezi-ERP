<?php

namespace App\Services\Inventory;

use App\Models\Lot;
use App\Models\StockMove;
use App\Models\StockMoveLine;
use App\Models\StockQuant;
use App\Models\StockReservation;
use Illuminate\Support\Facades\DB;

class StockReservationService
{
    public function __construct(private readonly StockQuantService $stockQuantService) {}

    /**
     * Reserve as much as possible for the given move from the given location using FEFO allocation.
     * Returns the reserved quantity (may be partial). Idempotent per move+location.
     */
    public function reserveForMove(StockMove $move, int $locationId): float
    {
        return DB::transaction(function () use ($move, $locationId) {
            // Check if we already reserved for this move+location
            $existingReservations = StockReservation::where('stock_move_id', $move->id)
                ->where('product_id', $move->product_id)
                ->where('location_id', $locationId)
                ->get();

            if ($existingReservations->isNotEmpty()) {
                return (float) $existingReservations->sum('quantity');
            }

            // Get available lots ordered by FEFO
            $availableLots = $this->stockQuantService->getAvailableLotsByFEFO(
                $move->company_id,
                $move->product_id,
                $locationId
            );

            $totalReserved = 0.0;
            $remainingToReserve = (float) $move->quantity;

            // Reserve from lots using FEFO
            foreach ($availableLots as $lotInfo) {
                if ($remainingToReserve <= 0) {
                    break;
                }

                $availableInLot = $lotInfo['available_quantity'];
                $toReserveFromLot = min($remainingToReserve, $availableInLot);

                if ($toReserveFromLot > 0) {
                    // Reserve from this specific lot
                    $this->stockQuantService->reserve(
                        $move->company_id,
                        $move->product_id,
                        $locationId,
                        $toReserveFromLot,
                        $lotInfo['lot_id']
                    );

                    $totalReserved += $toReserveFromLot;
                    $remainingToReserve -= $toReserveFromLot;
                }
            }

            // If no lots available, try to reserve from non-lot stock
            if ($totalReserved == 0) {
                $available = $this->stockQuantService->getAvailableQuantityByLot(
                    $move->company_id,
                    $move->product_id,
                    $locationId,
                    null // No lot
                );

                $toReserve = min($remainingToReserve, $available);
                if ($toReserve > 0) {
                    $this->stockQuantService->reserve(
                        $move->company_id,
                        $move->product_id,
                        $locationId,
                        $toReserve,
                        null
                    );

                    $totalReserved += $toReserve;
                }
            }

            // Create a single reservation record for the total reserved quantity
            if ($totalReserved > 0) {
                StockReservation::create([
                    'company_id' => $move->company_id,
                    'product_id' => $move->product_id,
                    'stock_move_id' => $move->id,
                    'location_id' => $locationId,
                    'quantity' => $totalReserved,
                ]);
            }

            return $totalReserved;
        });
    }

    /**
     * Consume reservations for a move: decrease quant and reserved together and delete reservation.
     * Creates StockMoveLines for lot tracking. Returns total consumed quantity.
     */
    public function consumeForMove(StockMove $move): float
    {
        return DB::transaction(function () use ($move) {
            $reservations = StockReservation::where('stock_move_id', $move->id)->lockForUpdate()->get();
            $total = 0.0;

            foreach ($reservations as $res) {
                // Get all quants with reserved quantity for this product/location
                $quants = StockQuant::where('company_id', $move->company_id)
                    ->where('product_id', $move->product_id)
                    ->where('location_id', $res->location_id)
                    ->where('reserved_quantity', '>', 0)
                    ->orderBy('lot_id') // Consistent ordering
                    ->lockForUpdate()
                    ->get();

                $remainingToConsume = (float) $res->quantity;

                foreach ($quants as $quant) {
                    if ($remainingToConsume <= 0) {
                        break;
                    }

                    $toConsumeFromQuant = min($remainingToConsume, $quant->reserved_quantity);

                    if ($toConsumeFromQuant > 0) {
                        // Adjust the quant (decrease both quantity and reserved)
                        $this->stockQuantService->adjust(
                            $move->company_id,
                            $move->product_id,
                            $res->location_id,
                            -$toConsumeFromQuant,
                            -$toConsumeFromQuant,
                            $quant->lot_id
                        );

                        // Create stock move line for traceability
                        StockMoveLine::create([
                            'company_id' => $move->company_id,
                            'stock_move_id' => $move->id,
                            'lot_id' => $quant->lot_id,
                            'quantity' => $toConsumeFromQuant,
                        ]);

                        $remainingToConsume -= $toConsumeFromQuant;
                        $total += $toConsumeFromQuant;
                    }
                }

                $res->delete();
            }

            return $total;
        });
    }

    /**
     * Allocate specific lots for a move using FEFO strategy
     */
    public function allocateLotsForMove(StockMove $move, int $locationId): array
    {
        $availableLots = $this->stockQuantService->getAvailableLotsByFEFO(
            $move->company_id,
            $move->product_id,
            $locationId
        );

        $allocations = [];
        $remainingQty = (float) $move->quantity;

        foreach ($availableLots as $lotInfo) {
            if ($remainingQty <= 0) {
                break;
            }

            $allocateFromLot = min($remainingQty, $lotInfo['available_quantity']);

            if ($allocateFromLot > 0) {
                $allocations[] = [
                    'lot_id' => $lotInfo['lot_id'],
                    'lot_code' => $lotInfo['lot_code'],
                    'quantity' => $allocateFromLot,
                    'expiration_date' => $lotInfo['expiration_date'],
                ];

                $remainingQty -= $allocateFromLot;
            }
        }

        return $allocations;
    }

    /**
     * Create stock move lines for lot-based moves
     */
    public function createMoveLines(StockMove $move, array $lotAllocations): void
    {
        foreach ($lotAllocations as $allocation) {
            StockMoveLine::create([
                'company_id' => $move->company_id,
                'stock_move_id' => $move->id,
                'lot_id' => $allocation['lot_id'],
                'quantity' => $allocation['quantity'],
            ]);
        }
    }

    /**
     * Release reservations for a move (used when cancelling)
     */
    public function releaseForMove(StockMove $move): void
    {
        // Delete all reservations for this move
        StockReservation::where('move_id', $move->id)->delete();
    }
}
