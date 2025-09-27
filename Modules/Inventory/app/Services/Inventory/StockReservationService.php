<?php

namespace Modules\Inventory\Services\Inventory;

use App\Models\Lot;
use App\Models\StockMove;
use App\Models\StockMoveLine;
use App\Models\StockQuant;
use App\Models\StockReservation;
use Illuminate\Support\Facades\DB;

/**
 * Stock Reservation Service
 *
 * This service manages stock reservations for order fulfillment, implementing FEFO (First Expired, First Out)
 * allocation for lot-tracked products and ensuring proper inventory allocation to prevent overselling.
 *
 * Key Features:
 * - FEFO allocation for lot-tracked products
 * - Atomic reservation operations with database locking
 * - Partial reservation support for insufficient stock
 * - Reservation consumption for order fulfillment
 * - Idempotent operations to prevent double reservations
 *
 * Business Rules:
 * - Reservations cannot exceed available quantities
 * - FEFO allocation prioritizes lots by expiration date
 * - Reservations are automatically consumed during stock moves
 * - All operations are atomic and thread-safe
 *
 * Lot Allocation Strategy:
 * - For lot-tracked products: FEFO (First Expired, First Out)
 * - For non-lot-tracked products: Simple quantity allocation
 * - Partial allocations supported when insufficient stock
 *
 * @package App\Services\Inventory
 * @author Laravel/Filament Inventory System
 * @version 1.0.0
 */
class StockReservationService
{
    /**
     * Create a new stock reservation service instance
     *
     * @param StockQuantService $stockQuantService Service for managing stock quantities
     */
    public function __construct(private readonly StockQuantService $stockQuantService) {}

    /**
     * Reserve stock for a move using FEFO allocation strategy
     *
     * This method reserves as much stock as possible for the given move from the specified
     * location using FEFO (First Expired, First Out) allocation for lot-tracked products.
     * The operation is idempotent - calling it multiple times for the same move and location
     * will return the same result without creating duplicate reservations.
     *
     * For lot-tracked products, the method prioritizes lots by expiration date (earliest first).
     * For non-lot-tracked products, it performs simple quantity allocation.
     *
     * @param StockMove $move The stock move requiring reservation
     * @param int $locationId Location to reserve stock from
     *
     * @return float The total quantity reserved (may be partial if insufficient stock)
     *
     * @example
     * $reservedQty = $service->reserveForMove($stockMove, $warehouseLocationId);
     * // Returns actual reserved quantity (e.g., 80.0 if only 80 available out of 100 requested)
     */
    public function reserveForMove(StockMove $move, int $locationId): float
    {
        return DB::transaction(function () use ($move, $locationId) {
            // Handle both old structure (direct product_id) and new structure (product lines)
            // For now, if this is a multi-product move, we'll reserve for the first product line
            // TODO: This service needs to be refactored to handle multi-product moves properly

            $productId = null;
            $quantity = 0;

            // Check if this is an old-style stock move with direct product_id
            if (isset($move->product_id) && $move->product_id) {
                $productId = $move->product_id;
                $quantity = $move->quantity;
            } else {
                // New structure - use first product line
                $firstProductLine = $move->productLines()->first();
                if ($firstProductLine) {
                    $productId = $firstProductLine->product_id;
                    $quantity = $firstProductLine->quantity;
                }
            }

            // If we couldn't determine product_id, return 0
            if (!$productId) {
                return 0.0;
            }

            // Check if we already reserved for this move+location
            $existingReservations = StockReservation::where('stock_move_id', $move->id)
                ->where('product_id', $productId)
                ->where('location_id', $locationId)
                ->get();

            if ($existingReservations->isNotEmpty()) {
                return (float) $existingReservations->sum('quantity');
            }

            // Get available lots ordered by FEFO
            $availableLots = $this->stockQuantService->getAvailableLotsByFEFO(
                $move->company_id,
                $productId,
                $locationId
            );

            $totalReserved = 0.0;
            $remainingToReserve = (float) $quantity;

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
                        $productId,
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
                    $productId,
                    $locationId,
                    null // No lot
                );

                $toReserve = min($remainingToReserve, $available);
                if ($toReserve > 0) {
                    $this->stockQuantService->reserve(
                        $move->company_id,
                        $productId,
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
                    'product_id' => $productId,
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
                // Find the product line for this reservation
                $productLine = $move->productLines()->where('product_id', $res->product_id)->first();
                if (!$productLine) {
                    continue; // Skip if no matching product line
                }

                // Get all quants with reserved quantity for this product/location
                $quants = StockQuant::where('company_id', $move->company_id)
                    ->where('product_id', $res->product_id)
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
                            $res->product_id,
                            $res->location_id,
                            -$toConsumeFromQuant,
                            -$toConsumeFromQuant,
                            $quant->lot_id
                        );

                        // Create stock move line for traceability
                        StockMoveLine::create([
                            'company_id' => $move->company_id,
                            'stock_move_product_line_id' => $productLine->id,
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
