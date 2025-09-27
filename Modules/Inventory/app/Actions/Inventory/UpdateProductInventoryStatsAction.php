<?php

namespace App\Actions\Inventory;

use App\Models\Product;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateProductInventoryStatsAction
{
    public function execute(Product $product, int $quantityChange, Money $purchasePricePerUnit): Product
    {
        return DB::transaction(function () use ($product, $quantityChange, $purchasePricePerUnit) {
            // Lock the product row to prevent race conditions during calculation.
            $product = Product::lockForUpdate()->findOrFail($product->id);

            $purchaseValue = $purchasePricePerUnit->multipliedBy($quantityChange, RoundingMode::HALF_UP);
            if (! $product->average_cost) {
                throw new \Exception('Product must have an average cost for inventory update');
            }
            $oldValue = $product->average_cost->multipliedBy($product->quantity_on_hand, RoundingMode::HALF_UP);
            $totalQuantity = $product->quantity_on_hand + $quantityChange;
            $totalValue = $oldValue->plus($purchaseValue);

            $newAverageCost = $totalQuantity > 0
                ? $totalValue->dividedBy($totalQuantity, RoundingMode::HALF_UP)
                : Money::zero($product->company->currency->code);

            Log::info('Before product update', [
                'product_id' => $product->id,
                'quantity_on_hand' => $product->quantity_on_hand,
                'average_cost' => $product->average_cost->getAmount()->toFloat(),
            ]);

            $product->forceFill([
                'quantity_on_hand' => $totalQuantity,
                'average_cost' => $newAverageCost,
            ])->save();

            $product->refresh();
            Log::info('After product update', [
                'product_id' => $product->id,
                'quantity_on_hand' => $product->quantity_on_hand,
                'average_cost' => $product->average_cost ? $product->average_cost->getAmount()->toFloat() : 0,
            ]);

            return $product;
        });
    }
}
