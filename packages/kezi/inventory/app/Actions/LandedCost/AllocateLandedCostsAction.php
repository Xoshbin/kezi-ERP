<?php

namespace Kezi\Inventory\Actions\LandedCost;

use Brick\Money\Money;
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

            if ($totalBasis instanceof Money ? $totalBasis->isZero() : $totalBasis == 0) {
                // Fallback or error? For now, just return to avoid division by zero.
                return;
            }

            foreach ($stockMoves as $move) {
                $moveBasis = $this->calculateMoveBasis($move, $allocationMethod);

                // Calculate ratio precisely
                if ($moveBasis instanceof Money && $totalBasis instanceof Money) {
                    $ratio = (float) $moveBasis->getAmount()->dividedBy($totalBasis->getAmount(), 8, \Brick\Math\RoundingMode::HALF_UP)->toFloat();
                } else {
                    $ratio = $moveBasis / $totalBasis;
                }

                $allocatedAmount = $totalAmount->multipliedBy($ratio, \Brick\Math\RoundingMode::HALF_UP);

                LandedCostLine::create([
                    'company_id' => $landedCost->company_id,
                    'landed_cost_id' => $landedCost->id,
                    'stock_move_id' => $move->id,
                    'additional_cost' => $allocatedAmount,
                ]);
            }
        });
    }

    private function calculateTotalBasis(Collection $stockMoves, LandedCostAllocationMethod $method): float|Money
    {
        return match ($method) {
            LandedCostAllocationMethod::ByQuantity => $stockMoves->sum(fn (StockMove $move) => $move->productLines->sum('quantity')),
            LandedCostAllocationMethod::ByCost => $this->sumCostImpact($stockMoves),
            LandedCostAllocationMethod::ByWeight => $stockMoves->sum(fn (StockMove $move) => $move->productLines->sum(fn ($line) => $line->product->weight * $line->quantity)),
            LandedCostAllocationMethod::ByVolume => $stockMoves->sum(fn (StockMove $move) => $move->productLines->sum(fn ($line) => $line->product->volume * $line->quantity)),
        };
    }

    private function calculateMoveBasis(StockMove $move, LandedCostAllocationMethod $method): float|Money
    {
        return match ($method) {
            LandedCostAllocationMethod::ByQuantity => $move->productLines->sum('quantity'),
            LandedCostAllocationMethod::ByCost => $this->sumMoveCostImpact($move),
            LandedCostAllocationMethod::ByWeight => $move->productLines->sum(fn ($line) => $line->product->weight * $line->quantity),
            LandedCostAllocationMethod::ByVolume => $move->productLines->sum(fn ($line) => $line->product->volume * $line->quantity),
        };
    }

    private function sumCostImpact(Collection $stockMoves): Money
    {
        $firstMove = $stockMoves->first();
        $currency = $firstMove->company->currency->code;
        $total = Money::of(0, $currency);

        foreach ($stockMoves as $move) {
            $total = $total->plus($this->sumMoveCostImpact($move));
        }

        return $total;
    }

    private function sumMoveCostImpact(StockMove $move): Money
    {
        $currency = $move->company->currency->code;
        $total = Money::of(0, $currency);

        foreach ($move->stockMoveValuations as $valuation) {
            $total = $total->plus($valuation->cost_impact);
        }

        return $total;
    }
}
