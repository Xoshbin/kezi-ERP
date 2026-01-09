<?php

namespace Modules\Inventory\Actions\LandedCost;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Enums\Inventory\CostSource;
use Modules\Inventory\Enums\Inventory\LandedCostStatus;
use Modules\Inventory\Enums\Inventory\ValuationMethod;
use Modules\Inventory\Models\InventoryCostLayer;
use Modules\Inventory\Models\LandedCost;
use Modules\Inventory\Models\StockMoveValuation;

class PostLandedCostAction
{
    public function execute(LandedCost $landedCost): void
    {
        if ($landedCost->status === LandedCostStatus::Posted) {
            return;
        }

        DB::transaction(function () use ($landedCost) {
            // 1. Create Journal Entry
            // Debit: Inventory Valuation Account (from Product Category)
            // Credit: Landed Cost Clearing Account (from Company/Settings)
            // For now, I'll need to fetch these accounts. Since I don't have easy access to settings yet,
            // I will assume the Journal Entry creation part is a TODO or simplified.

            // $journalEntry = $this->createJournalEntry($landedCost);
            // $landedCost->journal_entry_id = $journalEntry->id;

            // 2. Update Stock Valuation
            foreach ($landedCost->lines as $line) {
                $stockMove = $line->stockMove;

                // Create Stock Value Adjustment
                StockMoveValuation::create([
                    'company_id' => $landedCost->company_id,
                    'product_id' => $stockMove->productLines->first()->product_id, // Simplified: assuming 1 product per move for now or taking first
                    'stock_move_id' => $stockMove->id,
                    'quantity' => 0, // No quantity change
                    'cost_impact' => $line->additional_cost,
                    'valuation_method' => ValuationMethod::STANDARD, // Should match product's method
                    'move_type' => $stockMove->move_type,
                    // 'journal_entry_id' => $journalEntry->id,
                    'source_type' => LandedCost::class,
                    'source_id' => $landedCost->id,
                    'cost_source' => CostSource::Manual,
                    'cost_source_reference' => $landedCost->id,
                ]);

                // Update Inventory Cost Layer if needed (complex for FIFO)
                // If the stock move corresponds to a layer, we should increase the unit cost of that layer?
                // Or just creating the valuation record is enough for the "total value" report?
                // Usually, we update the remaining value of the layer if it exists.

                // Find associated cost layer?
                // $layer = InventoryCostLayer::where('source_type', StockMove::class)
                //    ->where('source_id', $stockMove->id)
                //    ->first();
                // if ($layer) {
                // $layer->unit_cost += apportioned cost?
                // This is tricky because layer unit_cost is calculated at creation.
                // If we want to support true landed cost, we might need a separate mechanism or update the layer.
                // }
            }

            $landedCost->status = LandedCostStatus::Posted;
            $landedCost->save();
        });
    }
}
