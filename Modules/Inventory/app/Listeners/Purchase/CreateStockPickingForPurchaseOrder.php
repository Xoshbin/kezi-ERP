<?php

namespace Modules\Inventory\Listeners\Purchase;

use Modules\Inventory\Enums\Inventory\InventoryAccountingMode;
use Modules\Inventory\Enums\Inventory\StockLocationType;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Enums\Inventory\StockPickingState;
use Modules\Inventory\Enums\Inventory\StockPickingType;
use Modules\Inventory\Models\StockLocation;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\StockMoveProductLine;
use Modules\Inventory\Models\StockPicking;
use Modules\Product\Enums\Products\ProductType;
use Modules\Purchase\Events\PurchaseOrderConfirmed;

class CreateStockPickingForPurchaseOrder
{
    /**
     * Handle the event.
     *
     * In AUTO_RECORD_ON_BILL mode, stock moves are created when the Vendor Bill is posted,
     * so we skip creation here to avoid duplicate stock moves.
     * In MANUAL_INVENTORY_RECORDING mode, we create draft stock moves for warehouse tracking.
     */
    public function handle(PurchaseOrderConfirmed $event): void
    {
        $po = $event->purchaseOrder;

        // In AUTO mode, bill posting will create stock moves automatically
        // So we skip PO-based stock move creation to avoid duplicates
        if ($po->company->inventory_accounting_mode === InventoryAccountingMode::AUTO_RECORD_ON_BILL) {
            return;
        }

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
            'name' => 'Vendors',
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
