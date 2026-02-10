<?php

namespace Kezi\Inventory\Services\Inventory;

use Illuminate\Support\Facades\DB;
use Kezi\Inventory\Models\Lot;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveLine;
use Kezi\Inventory\Models\StockQuant;
use Kezi\Inventory\Models\StockReservation;

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
 * @author Laravel/Filament Inventory System
 *
 * @version 1.0.0
 */
class StockReservationService
{
    /**
     * Create a new stock reservation service instance
     *
     * @param  StockQuantService  $stockQuantService  Service for managing stock quantities
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
     * @param  StockMove  $move  The stock move requiring reservation
     * @param  int  $locationId  Location to reserve stock from
     * @return float The total quantity reserved (may be partial if insufficient stock)
     *
     * @example
     * $reservedQty = $service->reserveForMove($stockMove, $warehouseLocationId);
     * // Returns actual reserved quantity (e.g., 80.0 if only 80 available out of 100 requested)
     */
    public function reserveForMove(StockMove $move, int $locationId): float
    {
        return DB::transaction(function () use ($move, $locationId) {
            $totalReservedOverall = 0.0;

            // Handle both old structure (direct product_id) and new structure (product lines)
            $productLinesData = [];

            // Check if this is an old-style stock move with direct product_id
            // Even if product_id is set, newer logic might have created product lines
            if (isset($move->product_id) && $move->product_id && $move->productLines()->count() === 0) {
                $productLinesData[] = [
                    'product_id' => $move->product_id,
                    'quantity' => $move->quantity,
                ];
            } else {
                // Use all product lines
                $productLines = $move->productLines()->get();
                foreach ($productLines as $line) {
                    $productLinesData[] = [
                        'product_id' => $line->product_id,
                        'quantity' => $line->quantity,
                    ];
                }
            }

            // Aggregate requirements by product to handle multi-line moves for the same product
            $requirements = [];
            foreach ($productLinesData as $lineData) {
                $pid = $lineData['product_id'];
                if ($pid) {
                    $requirements[$pid] = ($requirements[$pid] ?? 0.0) + (float) $lineData['quantity'];
                }
            }

            foreach ($requirements as $productId => $quantity) {
                // Check if we already reserved enough for this move+location+product
                $existingReservations = StockReservation::where('stock_move_id', $move->id)
                    ->where('product_id', $productId)
                    ->where('location_id', $locationId)
                    ->get();

                $alreadyReserved = (float) $existingReservations->sum('quantity');

                if ($alreadyReserved >= $quantity) {
                    $totalReservedOverall += $alreadyReserved;

                    continue;
                }

                $remainingToReserve = $quantity - $alreadyReserved;
                $newlyReservedForProduct = 0.0;

                // Get available lots ordered by FEFO
                $availableLots = $this->stockQuantService->getAvailableLotsByFEFO(
                    $move->company_id,
                    $productId,
                    $locationId
                );

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

                        $newlyReservedForProduct += $toReserveFromLot;
                        $remainingToReserve -= $toReserveFromLot;
                    }
                }

                // If still quantity remaining after lots, try to reserve from non-lot stock
                if ($remainingToReserve > 0) {
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

                        $newlyReservedForProduct += $toReserve;
                    }
                }

                // Create or update the reservation record
                if ($newlyReservedForProduct > 0) {
                    $reservation = $existingReservations->first();

                    if ($reservation) {
                        $reservation->increment('quantity', $newlyReservedForProduct);
                    } else {
                        StockReservation::create([
                            'company_id' => $move->company_id,
                            'product_id' => $productId,
                            'stock_move_id' => $move->id,
                            'location_id' => $locationId,
                            'quantity' => $newlyReservedForProduct,
                        ]);
                    }
                }

                $totalReservedOverall += ($alreadyReserved + $newlyReservedForProduct);
            }

            return $totalReservedOverall;
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
                if (! $productLine) {
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
        $reservations = StockReservation::where('stock_move_id', $move->id)->get();

        foreach ($reservations as $reservation) {
            $this->stockQuantService->unreserve(
                $reservation->company_id,
                $reservation->product_id,
                $reservation->location_id,
                $reservation->quantity
            );
            $reservation->delete();
        }
    }
}
