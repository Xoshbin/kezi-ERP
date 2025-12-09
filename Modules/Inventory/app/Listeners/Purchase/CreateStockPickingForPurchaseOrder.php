<?php

namespace Modules\Inventory\Listeners\Purchase;

use Modules\Purchase\Events\PurchaseOrderConfirmed;
use Modules\Inventory\Models\StockPicking;
use Modules\Inventory\Enums\Inventory\StockPickingType;
use Modules\Inventory\Enums\Inventory\StockPickingState;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Models\StockLocation;
use Modules\Inventory\Enums\Inventory\StockLocationType;
use Modules\Inventory\Models\StockMoveProductLine;
use Modules\Product\Enums\Products\ProductType;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateStockPickingForPurchaseOrder
{
    /**
     * Handle the event.
     */
    public function handle(PurchaseOrderConfirmed $event): void
    {
        $po = $event->purchaseOrder;

        // Filter lines for storable products
        $storableLines = $po->lines->filter(function ($line) {
            return $line->product && $line->product->type === ProductType::Storable;
        });

        if ($storableLines->isEmpty()) {
            return;
        }

        // Get Locations
        // Get Locations
        $vendorLocation = StockLocation::firstOrCreate([
            'company_id' => $po->company_id,
            'type' => StockLocationType::Vendor,
        ], [
            'name' => "Vendors",
            'is_active' => true,
        ]);

        $warehouseLocation = StockLocation::where('company_id', $po->company_id)
            ->where('type', StockLocationType::Internal)
            ->first();

        // Create Picking
        $picking = StockPicking::create([
            'company_id' => $po->company_id,
            'type' => StockPickingType::Receipt,
            'state' => StockPickingState::Draft,
            'partner_id' => $po->vendor_id,
            'scheduled_date' => $po->po_date ?? now(),
            'origin' => $po->po_number,
            'created_by_user_id' => auth()->id() ?? $po->created_by_user_id ?? \App\Models\User::first()->id,
        ]);

        // Create Moves
        foreach ($storableLines as $line) {
            $move = StockMove::create([
                'company_id' => $po->company_id,
                'picking_id' => $picking->id,
                'move_type' => StockMoveType::Incoming,
                'status' => StockMoveStatus::Draft,
                'move_date' => $po->po_date ?? now(),
                'created_by_user_id' => auth()->id() ?? $po->created_by_user_id,
                'reference' => $line->description,
                'description' => "Receive {$po->po_number}",
                'source_type' => get_class($line),
                'source_id' => $line->id,
            ]);

            // Create Move Product Line
            StockMoveProductLine::create([
                'company_id' => $po->company_id,
                'stock_move_id' => $move->id,
                'product_id' => $line->product_id,
                'quantity' => $line->quantity,
                'from_location_id' => $vendorLocation->id,
                'to_location_id' => $warehouseLocation->id,
            ]);
        }
    }
}
