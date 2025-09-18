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
    public function upsertQuant(int $companyId, int $productId, int $locationId): StockQuant
    {
        return StockQuant::firstOrCreate(
            [
                'company_id' => $companyId,
                'product_id' => $productId,
                'location_id' => $locationId,
            ],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
            ]
        );
    }

    public function adjust(int $companyId, int $productId, int $locationId, float $deltaQty, float $deltaReserved = 0): StockQuant
    {
        return DB::transaction(function () use ($companyId, $productId, $locationId, $deltaQty, $deltaReserved) {
            // Lock quant row for update to ensure atomicity
            $quant = StockQuant::where('company_id', $companyId)
                ->where('product_id', $productId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();

            if (! $quant) {
                $quant = $this->upsertQuant($companyId, $productId, $locationId);
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

    public function reserve(int $companyId, int $productId, int $locationId, float $qty): StockQuant
    {
        return $this->adjust($companyId, $productId, $locationId, 0, $qty);
    }

    public function unreserve(int $companyId, int $productId, int $locationId, float $qty): StockQuant
    {
        return $this->adjust($companyId, $productId, $locationId, 0, -$qty);
    }

    public function applyForIncoming(StockMove $move): void
    {
        $this->adjust($move->company_id, $move->product_id, $move->to_location_id, $move->quantity, 0);
    }

    public function applyForOutgoing(StockMove $move): void
    {
        $this->adjust($move->company_id, $move->product_id, $move->from_location_id, -$move->quantity, 0);
    }
}

