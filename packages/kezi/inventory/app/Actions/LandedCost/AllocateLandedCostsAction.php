<?php

namespace Kezi\Inventory\Actions\LandedCost;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kezi\Inventory\Enums\Inventory\LandedCostAllocationMethod;
use Kezi\Inventory\Models\LandedCost;
use Kezi\Inventory\Models\LandedCostLine;
use Kezi\Inventory\Models\StockMove;

class AllocateLandedCostsAction
{
    /**
     * @param  Collection<StockMove>  $stockMoves
     */
    public function execute(LandedCost $landedCost, Collection $stockMoves): void
    {
        if ($stockMoves->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($landedCost, $stockMoves) {
            // clear existing lines
            $landedCost->lines()->delete();

            $totalAmount = $landedCost->amount_total;
            $allocationMethod = $landedCost->allocation_method;

            // Calculate total basis
            $totalBasis = $this->calculateTotalBasis($stockMoves, $allocationMethod);

            if ($totalBasis == 0) {
                // Fallback or error? For now, just return to avoid division by zero.
                return;
            }

            foreach ($stockMoves as $move) {
                $moveBasis = $this->calculateMoveBasis($move, $allocationMethod);
                $ratio = $moveBasis / $totalBasis;

                $allocatedAmount = $totalAmount->multipliedBy($ratio); // Money handles multiplication/rounding

                LandedCostLine::create([
                    'company_id' => $landedCost->company_id,
                    'landed_cost_id' => $landedCost->id,
                    'stock_move_id' => $move->id,
                    'additional_cost' => $allocatedAmount,
                ]);
            }
        });
    }

    private function calculateTotalBasis(Collection $stockMoves, LandedCostAllocationMethod $method): float
    {
        return match ($method) {
            LandedCostAllocationMethod::ByQuantity => $stockMoves->sum(fn (StockMove $move) => $move->productLines->sum('quantity')),
            LandedCostAllocationMethod::ByCost => $stockMoves->sum(fn (StockMove $move) => $move->stockMoveValuations->sum(fn ($v) => $v->cost_impact->getAmount()->toFloat())), // Assuming cost_impact is Money
            LandedCostAllocationMethod::ByWeight => $stockMoves->sum(fn (StockMove $move) => $move->productLines->sum(fn ($line) => $line->product->weight * $line->quantity)),
            LandedCostAllocationMethod::ByVolume => $stockMoves->sum(fn (StockMove $move) => $move->productLines->sum(fn ($line) => $line->product->volume * $line->quantity)),
        };
    }

    private function calculateMoveBasis(StockMove $move, LandedCostAllocationMethod $method): float
    {
        return match ($method) {
            LandedCostAllocationMethod::ByQuantity => $move->productLines->sum('quantity'),
            LandedCostAllocationMethod::ByCost => $move->stockMoveValuations->sum(fn ($v) => $v->cost_impact->getAmount()->toFloat()),
            LandedCostAllocationMethod::ByWeight => $move->productLines->sum(fn ($line) => $line->product->weight * $line->quantity),
            LandedCostAllocationMethod::ByVolume => $move->productLines->sum(fn ($line) => $line->product->volume * $line->quantity),
        };
    }
}
