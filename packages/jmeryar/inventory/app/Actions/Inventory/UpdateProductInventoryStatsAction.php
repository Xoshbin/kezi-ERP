<?php

namespace Jmeryar\Inventory\Actions\Inventory;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jmeryar\Inventory\Services\Inventory\StockQuantService;
use Jmeryar\Product\Models\Product;

/**
 * Updates product inventory statistics (average cost) based on incoming purchases.
 *
 * Note: Quantity is now managed exclusively by StockQuant - this action only updates average_cost.
 */
class UpdateProductInventoryStatsAction
{
    public function __construct(
        private StockQuantService $stockQuantService,
    ) {}

    public function execute(Product $product, int $quantityChange, Money $purchasePricePerUnit): Product
    {
        return DB::transaction(function () use ($product, $quantityChange, $purchasePricePerUnit) {
            // Lock the product row to prevent race conditions during calculation.
            $product = Product::lockForUpdate()->findOrFail($product->id);

            // Get current quantity from StockQuant (source of truth)
            $currentQuantity = $this->stockQuantService->getTotalQuantity(
                $product->company_id,
                $product->id
            );

            $purchaseValue = $purchasePricePerUnit->multipliedBy($quantityChange, RoundingMode::HALF_UP);
            if (! $product->average_cost) {
                throw new Exception('Product must have an average cost for inventory update');
            }
            $oldValue = $product->average_cost->multipliedBy($currentQuantity, RoundingMode::HALF_UP);
            $totalQuantity = $currentQuantity + $quantityChange;
            $totalValue = $oldValue->plus($purchaseValue);

            $newAverageCost = $totalQuantity > 0
                ? $totalValue->dividedBy($totalQuantity, RoundingMode::HALF_UP)
                : Money::zero($product->company->currency->code);

            Log::info('Before product update', [
                'product_id' => $product->id,
                'current_quantity' => $currentQuantity,
                'average_cost' => $product->average_cost->getAmount()->toFloat(),
            ]);

            // Update only average_cost - quantity is managed by StockQuant
            $product->forceFill([
                'average_cost' => $newAverageCost,
            ])->save();

            $product->refresh();
            Log::info('After product update', [
                'product_id' => $product->id,
                'total_quantity' => $totalQuantity,
                'average_cost' => $product->average_cost ? $product->average_cost->getAmount()->toFloat() : 0,
            ]);

            return $product;
        });
    }
}
