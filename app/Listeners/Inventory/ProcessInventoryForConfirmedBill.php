<?php

namespace App\Listeners\Inventory;

use RuntimeException;
use App\Actions\Inventory\UpdateProductInventoryStatsAction;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Products\ProductType;
use App\Events\VendorBillConfirmed;
use App\Models\StockMove;

class ProcessInventoryForConfirmedBill
{
    public function __construct(private readonly UpdateProductInventoryStatsAction $updateProductInventoryStatsAction)
    {
    }

    public function handle(VendorBillConfirmed $event): void
    {
        $vendorBill = $event->vendorBill;
        $user = $event->user;

        foreach ($vendorBill->lines as $line) {
            if ($line->product?->type === ProductType::Storable) {
                $this->processStorableProductLine($vendorBill, $line, $user);
            }
        }
    }

    private function processStorableProductLine($vendorBill, $line, $user): void
    {
        $product = $line->product;
        $company = $vendorBill->company;

        if (!$company->vendorLocation || !$company->defaultStockLocation) {
            throw new RuntimeException("Default Vendor or Stock Location is not configured for Company ID: {$company->id}.");
        }

        StockMove::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => $line->quantity,
            'from_location_id' => $company->vendorLocation->id,
            'to_location_id' => $company->defaultStockLocation->id,
            'source_type' => get_class($vendorBill),
            'source_id' => $vendorBill->id,
            'move_date' => $vendorBill->accounting_date,
            'created_by_user_id' => $user->id,
            'move_type' => StockMoveType::Incoming,
            'status' => StockMoveStatus::Done,
            'completed_at' => now(),
        ]);

        $this->updateProductInventoryStatsAction->execute(
            $product,
            $line->quantity,
            $line->unit_price
        );
    }
}
