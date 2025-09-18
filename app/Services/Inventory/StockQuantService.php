<?php

namespace App\Services\Inventory;

use App\Models\Company;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\StockMove;
use App\Models\StockQuant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockQuantService
{
    public function upsertQuant(int $companyId, int $productId, int $locationId, ?int $lotId = null): StockQuant
    {
        return StockQuant::firstOrCreate(
            [
                'company_id' => $companyId,
                'product_id' => $productId,
                'location_id' => $locationId,
                'lot_id' => $lotId,
            ],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
            ]
        );
    }

    public function adjust(int $companyId, int $productId, int $locationId, float $deltaQty, float $deltaReserved = 0, ?int $lotId = null): StockQuant
    {
        return DB::transaction(function () use ($companyId, $productId, $locationId, $deltaQty, $deltaReserved, $lotId) {
            // Lock quant row for update to ensure atomicity
            $quant = StockQuant::where('company_id', $companyId)
                ->where('product_id', $productId)
                ->where('location_id', $locationId)
                ->where('lot_id', $lotId)
                ->lockForUpdate()
                ->first();

            if (! $quant) {
                $quant = $this->upsertQuant($companyId, $productId, $locationId, $lotId);
                $quant->refresh();
                $quant->lockForUpdate();
            }

            $newQty = $quant->quantity + $deltaQty;
            $newReserved = $quant->reserved_quantity + $deltaReserved;

            if ($newQty < 0) {
                throw new RuntimeException('Insufficient quantity for adjustment');
            }
            if ($newReserved < 0) {
                throw new RuntimeException('Reserved quantity cannot be negative');
            }
            if ($newReserved > $newQty) {
                throw new RuntimeException('Reserved quantity cannot exceed available quantity');
            }

            $quant->forceFill([
                'quantity' => $newQty,
                'reserved_quantity' => $newReserved,
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

    public function reserve(int $companyId, int $productId, int $locationId, float $qty, ?int $lotId = null): StockQuant
    {
        return $this->adjust($companyId, $productId, $locationId, 0, $qty, $lotId);
    }

    public function unreserve(int $companyId, int $productId, int $locationId, float $qty, ?int $lotId = null): StockQuant
    {
        return $this->adjust($companyId, $productId, $locationId, 0, -$qty, $lotId);
    }

    public function applyForIncoming(StockMove $move): void
    {
        $this->adjust($move->company_id, $move->product_id, $move->to_location_id, $move->quantity, 0);
    }

    public function applyForOutgoing(StockMove $move): void
    {
        $this->adjust($move->company_id, $move->product_id, $move->from_location_id, -$move->quantity, 0);
    }

    /**
     * Get available quantity for a product at a location, optionally filtered by lot
     */
    public function getAvailableQuantityByLot(int $companyId, int $productId, int $locationId, ?int $lotId = null): float
    {
        $query = StockQuant::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('location_id', $locationId);

        if ($lotId !== null) {
            $query->where('lot_id', $lotId);
        }

        $quant = $query->first();

        if (!$quant) {
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
                return $quant->lot && !$quant->lot->isExpired();
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
        $this->adjust($move->company_id, $move->product_id, $move->to_location_id, $move->quantity, 0, $lotId);
    }

    /**
     * Apply lot-aware outgoing stock update
     */
    public function applyForOutgoingWithLot(StockMove $move, ?int $lotId = null): void
    {
        $this->adjust($move->company_id, $move->product_id, $move->from_location_id, -$move->quantity, 0, $lotId);
    }
}
