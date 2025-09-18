<?php

namespace App\Services\Inventory;

use App\Models\StockMove;
use App\Models\StockReservation;
use Illuminate\Support\Facades\DB;

class StockReservationService
{
    public function __construct(private readonly StockQuantService $stockQuantService) {}

    /**
     * Reserve as much as possible for the given move from the given location.
     * Returns the reserved quantity (may be partial). Idempotent per move+location.
     */
    public function reserveForMove(StockMove $move, int $locationId): float
    {
        return DB::transaction(function () use ($move, $locationId) {
            // Check if we already reserved for this move+location
            $existing = StockReservation::where('stock_move_id', $move->id)
                ->where('product_id', $move->product_id)
                ->where('location_id', $locationId)
                ->first();
            if ($existing) {
                return (float) $existing->quantity;
            }

            $available = $this->stockQuantService->available(
                $move->company_id,
                $move->product_id,
                $locationId
            );

            $toReserve = min((float) $move->quantity, $available);
            if ($toReserve <= 0) {
                return 0.0;
            }

            // Increase reserved quantity on quant
            $this->stockQuantService->reserve(
                $move->company_id,
                $move->product_id,
                $locationId,
                $toReserve
            );

            // Create reservation record
            StockReservation::create([
                'company_id' => $move->company_id,
                'product_id' => $move->product_id,
                'stock_move_id' => $move->id,
                'location_id' => $locationId,
                'quantity' => $toReserve,
            ]);

            return $toReserve;
        });
    }

    /**
     * Consume reservations for a move: decrease quant and reserved together and delete reservation.
     * Returns total consumed quantity.
     */
    public function consumeForMove(StockMove $move): float
    {
        return DB::transaction(function () use ($move) {
            $reservations = StockReservation::where('stock_move_id', $move->id)->lockForUpdate()->get();
            $total = 0.0;
            foreach ($reservations as $res) {
                $this->stockQuantService->adjust(
                    $move->company_id,
                    $move->product_id,
                    $res->location_id,
                    -1 * (float) $res->quantity,
                    -1 * (float) $res->quantity
                );
                $total += (float) $res->quantity;
                $res->delete();
            }
            return $total;
        });
    }
}

