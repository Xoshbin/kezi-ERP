<?php

namespace App\Listeners\Inventory;

use App\Actions\Inventory\UpdateProductInventoryStatsAction;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Products\ProductType;
use App\Events\VendorBillConfirmed;
use App\Models\StockMove;
use RuntimeException;

class ProcessInventoryForConfirmedBill
{
    public function __construct(private readonly UpdateProductInventoryStatsAction $updateProductInventoryStatsAction) {}

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

    private function processStorableProductLine(\App\Models\VendorBill $vendorBill, \App\Models\VendorBillLine $line, \App\Models\User $user): void
    {
        $product = $line->product;
        $company = $vendorBill->company;

        // Ensure product exists (should not be null since we check for storable products)
        if ($product === null) {
            throw new RuntimeException("Product is required for storable product lines.");
        }

        if (! $company->vendorLocation || ! $company->defaultStockLocation) {
            throw new RuntimeException("Default Vendor or Stock Location is not configured for Company ID: {$company->id}.");
        }

        /** @var \App\Models\StockLocation $vendorLocation */
        $vendorLocation = $company->vendorLocation;
        /** @var \App\Models\StockLocation $defaultStockLocation */
        $defaultStockLocation = $company->defaultStockLocation;

        StockMove::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => $line->quantity,
            'from_location_id' => $vendorLocation->id,
            'to_location_id' => $defaultStockLocation->id,
            'source_type' => get_class($vendorBill),
            'source_id' => $vendorBill->id,
            'move_date' => $vendorBill->accounting_date,
            'created_by_user_id' => $user->id,
            'move_type' => StockMoveType::Incoming,
            'status' => StockMoveStatus::Done,
            'completed_at' => now(),
        ]);

        // Use company currency unit price for inventory calculations
        $unitPriceInCompanyCurrency = $line->unit_price_company_currency ?? $line->unit_price;

        $this->updateProductInventoryStatsAction->execute(
            $product,
            $line->quantity,
            $unitPriceInCompanyCurrency
        );
    }
}
