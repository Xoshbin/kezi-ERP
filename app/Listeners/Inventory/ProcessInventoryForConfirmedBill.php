<?php

namespace App\Listeners\Inventory;

use App\Events\VendorBillConfirmed;
use App\Enums\Products\ProductType;
use App\Models\StockMove;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Inventory\StockMoveStatus;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

class ProcessInventoryForConfirmedBill
{
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
        $product->refresh();
        $company = $vendorBill->company->fresh();

        if (!$company->vendorLocation || !$company->defaultStockLocation) {
            throw new \RuntimeException("Default Vendor or Stock Location is not configured for Company ID: {$company->id}.");
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
            'created_by_user_id' => $user->id, // We now have the user context
            'move_type' => StockMoveType::INCOMING,
            'status' => StockMoveStatus::DONE,
            'completed_at' => now(),
        ]);

        $purchaseValue = $line->unit_price->multipliedBy((string)$line->quantity, RoundingMode::HALF_UP);
        $oldValue = ($product->average_cost ?? Money::zero($vendorBill->currency->code))->multipliedBy((string)($product->quantity_on_hand ?? 0), RoundingMode::HALF_UP);
        $totalQuantity = ($product->quantity_on_hand ?? 0) + $line->quantity;
        $totalValue = $oldValue->plus($purchaseValue);

        $newAverageCost = $totalQuantity > 0
            ? $totalValue->dividedBy($totalQuantity, RoundingMode::HALF_UP)
            : Money::zero($vendorBill->currency->code);

        $product->quantity_on_hand = $totalQuantity;
        $product->average_cost = $newAverageCost;
        $product->save();
    }
}
