<?php

namespace Kezi\Manufacturing\Actions;

use Exception;
use Illuminate\Support\Facades\DB;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockMove;
use Kezi\Manufacturing\Models\ManufacturingOrder;

class ScrapManufacturingAction
{
    /**
     * Scrap items from a Manufacturing Order.
     *
     * @param  array<int, array{product_id: int, quantity: float}>  $items  Array of items to scrap.
     *
     * @throws Exception
     */
    public function execute(ManufacturingOrder $manufacturingOrder, array $items): void
    {
        $company = $manufacturingOrder->company;

        // Find a Scrap location for the company
        $scrapLocation = StockLocation::where('company_id', $company->id)
            ->ofType(StockLocationType::Scrap)
            ->first();

        if (! $scrapLocation) {
            throw new Exception("No Scrap location found for company {$company->name}. Please configure one.");
        }

        DB::transaction(function () use ($manufacturingOrder, $items, $scrapLocation) {
            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantity = $item['quantity'];

                // Create Stock Move
                $stockMove = StockMove::create([
                    'company_id' => $manufacturingOrder->company_id,
                    'product_id' => $productId, // Deprecated but often required by older logic, can be null if lines used
                    'source_location_id' => $manufacturingOrder->source_location_id,
                    'destination_location_id' => $scrapLocation->id,
                    'move_type' => StockMoveType::Outgoing, // Outgoing to Scrap
                    'status' => StockMoveStatus::Draft,
                    'reference' => 'SCRAP-'.$manufacturingOrder->number,
                    'source_type' => ManufacturingOrder::class,
                    'source_id' => $manufacturingOrder->id,
                    'scheduled_date' => now(),
                    'move_date' => now(),
                    'created_by_user_id' => \Illuminate\Support\Facades\Auth::id() ?? 1,
                ]);

                // Create Stock Move Line
                $productLine = \Kezi\Inventory\Models\StockMoveProductLine::create([
                    'company_id' => $manufacturingOrder->company_id,
                    'stock_move_id' => $stockMove->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'from_location_id' => $manufacturingOrder->source_location_id,
                    'to_location_id' => $scrapLocation->id,
                ]);

                \Kezi\Inventory\Models\StockMoveLine::create([
                    'company_id' => $manufacturingOrder->company_id,
                    'stock_move_product_line_id' => $productLine->id,
                    'quantity' => $quantity,
                ]);

                // Confirm the Stock Move (this triggers the Observer -> ProcessOutgoingStock -> Accounting)
                $stockMove->update(['status' => StockMoveStatus::Done]);
            }
        });
    }
}
