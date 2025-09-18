<?php

namespace App\Listeners\Inventory;

use App\Actions\Inventory\UpdateProductInventoryStatsAction;
use App\Enums\Products\ProductType;
use App\Events\VendorBillConfirmed;
use RuntimeException;

class ProcessInventoryForConfirmedBill
{
    public function __construct(private readonly UpdateProductInventoryStatsAction $updateProductInventoryStatsAction) {}

    public function handle(VendorBillConfirmed $event): void
    {
        $vendorBill = $event->vendorBill;

        foreach ($vendorBill->lines as $line) {
            if ($line->product?->type === ProductType::Storable) {
                $this->processStorableProductLine($line);
            }
        }
    }

    private function processStorableProductLine(\App\Models\VendorBillLine $line): void
    {
        $product = $line->product;

        // Ensure product exists (should not be null since we check for storable products)
        if ($product === null) {
            throw new RuntimeException('Product is required for storable product lines.');
        }

        // Use company currency unit price for inventory calculations
        $unitPriceInCompanyCurrency = $line->unit_price_company_currency ?? $line->unit_price;

        // Only update inventory statistics here; stock moves are created by VendorBillService::post().
        $this->updateProductInventoryStatsAction->execute(
            $product,
            (int) $line->quantity,
            $unitPriceInCompanyCurrency
        );
    }
}
