<?php

namespace App\Actions\Inventory;

use App\Models\Product;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;

class UpdateProductInventoryStatsAction
{
    public function execute(Product $product, int $quantityChange, Money $purchasePricePerUnit): Product
    {
        return DB::transaction(function () use ($product, $quantityChange, $purchasePricePerUnit) {
            // Lock the product row to prevent race conditions during calculation.
            $product = Product::lockForUpdate()->find($product->id);

            // Ensure all calculations are in the company's base currency
            $companyCurrency = $product->company->currency;
            $purchaseCurrency = $purchasePricePerUnit->getCurrency();

            // Convert purchase price to company base currency if needed
            if ($companyCurrency->code !== $purchaseCurrency->getCurrencyCode()) {
                // Find the exchange rate for the purchase currency
                $foreignCurrency = \App\Models\Currency::where('code', $purchaseCurrency->getCurrencyCode())->first();
                if (!$foreignCurrency) {
                    throw new \RuntimeException("Currency {$purchaseCurrency->getCurrencyCode()} not found in system");
                }
                $exchangeRate = $foreignCurrency->exchange_rate;
                $purchasePriceInBaseCurrency = Money::of($purchasePricePerUnit->getAmount(), $companyCurrency->code)->multipliedBy($exchangeRate, RoundingMode::HALF_UP);
            } else {
                $purchasePriceInBaseCurrency = $purchasePricePerUnit;
            }

            $purchaseValue = $purchasePriceInBaseCurrency->multipliedBy($quantityChange, RoundingMode::HALF_UP);
            $oldValue = $product->average_cost->multipliedBy($product->quantity_on_hand, RoundingMode::HALF_UP);
            $totalQuantity = $product->quantity_on_hand + $quantityChange;
            $totalValue = $oldValue->plus($purchaseValue);

            $newAverageCost = $totalQuantity > 0
                ? $totalValue->dividedBy($totalQuantity, RoundingMode::HALF_UP)
                : Money::zero($companyCurrency->code);

            \Illuminate\Support\Facades\Log::info('Before product update', [
                'product_id' => $product->id,
                'quantity_on_hand' => $product->quantity_on_hand,
                'average_cost' => $product->average_cost->getAmount()->toFloat(),
            ]);

            $product->forceFill([
                'quantity_on_hand' => $totalQuantity,
                'average_cost' => $newAverageCost,
            ])->save();

            $product->refresh();
            \Illuminate\Support\Facades\Log::info('After product update', [
                'product_id' => $product->id,
                'quantity_on_hand' => $product->quantity_on_hand,
                'average_cost' => $product->average_cost->getAmount()->toFloat(),
            ]);

            return $product;
        });
    }
}
