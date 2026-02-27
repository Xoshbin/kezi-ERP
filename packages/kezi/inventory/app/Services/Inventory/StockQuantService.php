<?php

namespace Kezi\Inventory\Services\Inventory;

use Illuminate\Support\Facades\DB;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveProductLine;
use Kezi\Inventory\Models\StockQuant;
use RuntimeException;

/**
 * Stock Quantity Service
 *
 * This service manages stock quantities (quants) across all locations and lots, providing
 * atomic operations for quantity updates, availability checks, and FEFO (First Expired, First Out)
 * allocation for lot-tracked products.
 *
 * Key Features:
 * - Atomic quantity updates with database locking
 * - Reservation management for order fulfillment
 * - FEFO allocation for lot-tracked products
 * - Availability calculations considering reservations
 * - Multi-location and multi-lot support
 *
 * Business Rules:
 * - Quantities cannot go negative (enforced with validation)
 * - Reserved quantities cannot exceed available quantities
 * - FEFO allocation prioritizes lots by expiration date
 * - All operations are atomic and thread-safe
 *
 * @author Laravel/Filament Inventory System
 *
 * @version 1.0.0
 */
class StockQuantService
{
    /**
     * Global flag to allow negative stock quantities during certain operations (like POS sync).
     */
    public static bool $allowNegativeStock = false;

    /**
     * Create or retrieve a stock quant for the given parameters
     *
     * This method ensures a StockQuant record exists for the specified combination
     * of company, product, location, lot, and serial number. If the record doesn't exist,
     * it creates one with zero quantities.
     *
     * @param  int  $companyId  Company identifier
     * @param  int  $productId  Product identifier
     * @param  int  $locationId  Location identifier
     * @param  int|null  $lotId  Lot identifier (null for non-lot-tracked products)
     * @param  int|null  $serialNumberId  Serial number identifier (null for non-serial-tracked products)
     * @return StockQuant The existing or newly created stock quant
     *
     * @example
     * $quant = $service->upsertQuant(1, 123, 456, 789, 101);
     * // Returns StockQuant with quantity=0, reserved_quantity=0 if new
     */
    public function upsertQuant(int $companyId, int $productId, int $locationId, ?int $lotId = null, ?int $serialNumberId = null): StockQuant
    {
        return StockQuant::firstOrCreate(
            [
                'company_id' => $companyId,
                'product_id' => $productId,
                'location_id' => $locationId,
                'lot_id' => $lotId,
                'serial_number_id' => $serialNumberId,
            ],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
            ]
        );
    }

    /**
     * Atomically adjust stock quantities with validation
     *
     * This method performs atomic updates to stock quantities using database locking
     * to prevent race conditions. It validates business rules and ensures data integrity
     * by checking that quantities don't go negative and reservations don't exceed available stock.
     *
     * For serial-tracked products, enforces quantity = 1 constraint.
     *
     * @param  int  $companyId  Company identifier
     * @param  int  $productId  Product identifier
     * @param  int  $locationId  Location identifier
     * @param  float  $deltaQty  Change in quantity (positive for increases, negative for decreases)
     * @param  float  $deltaReserved  Change in reserved quantity (default: 0)
     * @param  int|null  $lotId  Lot identifier (null for non-lot-tracked products)
     * @param  int|null  $serialNumberId  Serial number identifier (null for non-serial-tracked products)
     * @return StockQuant The updated stock quant
     *
     * @throws RuntimeException When insufficient quantity for adjustment
     * @throws RuntimeException When serial-tracked product quantity would exceed 1
     */
    public function adjust(int $companyId, int $productId, int $locationId, float $deltaQty, float $deltaReserved = 0, ?int $lotId = null, ?int $serialNumberId = null): StockQuant
    {
        return DB::transaction(function () use ($companyId, $productId, $locationId, $deltaQty, $deltaReserved, $lotId, $serialNumberId) {
            // Lock quant row for update to ensure atomicity
            $quant = StockQuant::where('company_id', $companyId)
                ->where('product_id', $productId)
                ->where('location_id', $locationId)
                ->where('lot_id', $lotId)
                ->where('serial_number_id', $serialNumberId)
                ->lockForUpdate()
                ->first();

            if (! $quant) {
                $quant = $this->upsertQuant($companyId, $productId, $locationId, $lotId, $serialNumberId);
                $quant->refresh();
                $quant->lockForUpdate();
            }

            $newQty = $quant->quantity + $deltaQty;
            $newReserved = $quant->reserved_quantity + $deltaReserved;

            // Serial-tracked products must have quantity of 0 or 1
            if ($serialNumberId && $newQty > 1) {
                throw new RuntimeException('Serial-tracked product quantity cannot exceed 1');
            }

            if ($newQty < 0 && ! self::$allowNegativeStock) {
                throw new RuntimeException('Insufficient quantity for adjustment');
            }
            if ($newReserved < 0) {
                throw new RuntimeException('Reserved quantity cannot be negative');
            }
            if ($newReserved > $newQty && ! self::$allowNegativeStock) {
                throw new RuntimeException('Reserved quantity cannot exceed available quantity');
            }

            $quant->forceFill([
                'quantity' => $newQty,
                'reserved_quantity' => $newReserved,
                'is_negative_stock' => $newQty < 0,
            ])->save();

            return $quant;
        });
    }

    public function available(int $companyId, int $productId, ?int $locationId = null): float
    {
        $query = StockQuant::where('company_id', $companyId)
            ->where('product_id', $productId);
        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        $totalQty = (float) $query->sum('quantity');
        $totalReserved = (float) $query->sum('reserved_quantity');

        return $totalQty - $totalReserved;
    }

    public function getTotalQuantity(int $companyId, int $productId, ?int $locationId = null): float
    {
        $query = StockQuant::where('company_id', $companyId)
            ->where('product_id', $productId);
        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return (float) $query->sum('quantity');
    }

    public function getReservedQuantity(int $companyId, int $productId, ?int $locationId = null): float
    {
        $query = StockQuant::where('company_id', $companyId)
            ->where('product_id', $productId);
        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return (float) $query->sum('reserved_quantity');
    }

    public function reserve(int $companyId, int $productId, int $locationId, float $qty, ?int $lotId = null, ?int $serialNumberId = null): StockQuant
    {
        return $this->adjust($companyId, $productId, $locationId, 0, $qty, $lotId, $serialNumberId);
    }

    public function unreserve(int $companyId, int $productId, int $locationId, float $qty, ?int $lotId = null, ?int $serialNumberId = null): StockQuant
    {
        return $this->adjust($companyId, $productId, $locationId, 0, -$qty, $lotId, $serialNumberId);
    }

    public function applyForIncoming(StockMove $move): void
    {
        // Handle both old structure (direct product_id) and new structure (product lines)
        if (isset($move->product_id) && $move->product_id) {
            // Old structure
            $this->adjust($move->company_id, $move->product_id, $move->to_location_id, $move->quantity, 0);
        } else {
            // New structure - apply for all product lines
            foreach ($move->productLines as $productLine) {
                $this->applyForIncomingProductLine($productLine);
            }
        }
    }

    public function applyForOutgoing(StockMove $move): void
    {
        // Handle both old structure (direct product_id) and new structure (product lines)
        if (isset($move->product_id) && $move->product_id) {
            // Old structure
            $this->adjust($move->company_id, $move->product_id, $move->from_location_id, -$move->quantity, 0);
        } else {
            // New structure - apply for all product lines
            foreach ($move->productLines as $productLine) {
                $this->applyForOutgoingProductLine($productLine);
            }
        }
    }

    public function applyForIncomingProductLine(StockMoveProductLine $productLine): void
    {
        // If product line has detailed moves (lots/serials), process them
        if ($productLine->stockMoveLines()->exists()) {
            foreach ($productLine->stockMoveLines as $moveLine) {
                $this->adjust(
                    $moveLine->company_id,
                    $productLine->product_id,
                    $productLine->to_location_id,
                    $moveLine->quantity,
                    0,
                    $moveLine->lot_id,
                    $moveLine->serial_number_id
                );
            }
        } else {
            // Otherwise process as bulk move
            $this->adjust($productLine->company_id, $productLine->product_id, $productLine->to_location_id, $productLine->quantity, 0);
        }
    }

    public function applyForOutgoingProductLine(StockMoveProductLine $productLine): void
    {
        // If product line has detailed moves (lots/serials), process them
        if ($productLine->stockMoveLines()->exists()) {
            foreach ($productLine->stockMoveLines as $moveLine) {
                $this->adjust(
                    $moveLine->company_id,
                    $productLine->product_id,
                    $productLine->from_location_id,
                    -$moveLine->quantity,
                    0,
                    $moveLine->lot_id,
                    $moveLine->serial_number_id
                );
            }
        } else {
            // Otherwise process as bulk move
            $this->adjust($productLine->company_id, $productLine->product_id, $productLine->from_location_id, -$productLine->quantity, 0);
        }
    }

    /**
     * Get available quantity for a product at a location, optionally filtered by lot
     */
    public function getAvailableQuantityByLot(int $companyId, int $productId, int $locationId, ?int $lotId = null): float
    {
        $query = StockQuant::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('location_id', $locationId);

        $query->where('lot_id', $lotId);

        $quant = $query->first();

        if (! $quant) {
            return 0.0;
        }

        return $quant->quantity - $quant->reserved_quantity;
    }

    /**
     * Get all lots with available quantity for a product at a location, ordered by expiration (FEFO)
     */
    public function getAvailableLotsByFEFO(int $companyId, int $productId, int $locationId): array
    {
        $quants = StockQuant::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->whereNotNull('lot_id')
            ->whereRaw('quantity > reserved_quantity')
            ->with(['lot' => function ($query) {
                $query->notExpired()->orderByExpiration();
            }])
            ->get()
            ->filter(function ($quant) {
                return $quant->lot && ! $quant->lot->isExpired();
            })
            ->sortBy(function ($quant) {
                return $quant->lot->expiration_date ?? '9999-12-31';
            });

        return $quants->map(function ($quant) {
            return [
                'lot_id' => $quant->lot_id,
                'lot_code' => $quant->lot->lot_code,
                'expiration_date' => $quant->lot->expiration_date,
                'available_quantity' => $quant->quantity - $quant->reserved_quantity,
            ];
        })->toArray();
    }

    /**
     * Apply lot-aware incoming stock update
     */
    public function applyForIncomingWithLot(StockMove $move, ?int $lotId = null): void
    {
        // Handle both old structure (direct product_id) and new structure (product lines)
        if (isset($move->product_id) && $move->product_id) {
            // Old structure
            $this->adjust($move->company_id, $move->product_id, $move->to_location_id, $move->quantity, 0, $lotId);
        } else {
            // New structure - apply for first product line
            $firstProductLine = $move->productLines()->first();
            if ($firstProductLine) {
                $this->adjust($move->company_id, $firstProductLine->product_id, $firstProductLine->to_location_id, $firstProductLine->quantity, 0, $lotId);
            }
        }
    }

    /**
     * Apply lot-aware outgoing stock update
     */
    public function applyForOutgoingWithLot(StockMove $move, ?int $lotId = null): void
    {
        // Handle both old structure (direct product_id) and new structure (product lines)
        if (isset($move->product_id) && $move->product_id) {
            // Old structure
            $this->adjust($move->company_id, $move->product_id, $move->from_location_id, -$move->quantity, 0, $lotId);
        } else {
            // New structure - apply for first product line
            $firstProductLine = $move->productLines()->first();
            if ($firstProductLine) {
                $this->adjust($move->company_id, $firstProductLine->product_id, $firstProductLine->from_location_id, -$firstProductLine->quantity, 0, $lotId);
            }
        }
    }
}
