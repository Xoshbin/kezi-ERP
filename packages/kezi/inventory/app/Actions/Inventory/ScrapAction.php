<?php

namespace Kezi\Inventory\Actions\Inventory;

use Exception;
use Illuminate\Support\Facades\DB;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveLine;
use Kezi\Inventory\Models\StockMoveProductLine;

class ScrapAction
{
    /**
     * Scrap items from a specific location.
     *
     * @param  array<int, array{product_id: int, quantity: float, lot_id?: int, serial_number_id?: int}>  $items
     *
     * @throws Exception
     */
    public function execute(int $companyId, int $sourceLocationId, array $items, string $reference, ?string $sourceType = null, ?int $sourceId = null): void
    {
        $scrapLocation = StockLocation::where('company_id', $companyId)
            ->ofType(StockLocationType::Scrap)
            ->first();

        if (! $scrapLocation) {
            throw new Exception('No Scrap location found. Please configure one.');
        }

        DB::transaction(function () use ($companyId, $sourceLocationId, $items, $scrapLocation, $reference, $sourceType, $sourceId) {
            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantity = $item['quantity'];

                $stockMove = StockMove::create([
                    'company_id' => $companyId,
                    'source_location_id' => $sourceLocationId,
                    'destination_location_id' => $scrapLocation->id,
                    'move_type' => StockMoveType::Outgoing,
                    'status' => StockMoveStatus::Draft,
                    'reference' => $reference,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'scheduled_date' => now(),
                    'move_date' => now(),
                    'created_by_user_id' => auth()->id() ?? 1,
                ]);

                $productLine = StockMoveProductLine::create([
                    'company_id' => $companyId,
                    'stock_move_id' => $stockMove->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'from_location_id' => $sourceLocationId,
                    'to_location_id' => $scrapLocation->id,
                ]);

                StockMoveLine::create([
                    'company_id' => $companyId,
                    'stock_move_product_line_id' => $productLine->id,
                    'quantity' => $quantity,
                    'lot_id' => $item['lot_id'] ?? null,
                    'serial_number_id' => $item['serial_number_id'] ?? null,
                ]);

                $stockMove->update(['status' => StockMoveStatus::Done]);
            }
        });
    }
}
