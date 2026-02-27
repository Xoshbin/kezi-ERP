<?php

use App\Models\Company;
use Illuminate\Support\Facades\Event;
use Kezi\Inventory\Events\ProductStockUpdated;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockQuant;
use Kezi\Product\Models\Product;

it('dispatches ProductStockUpdated event when StockQuant is saved', function () {
    Event::fake([ProductStockUpdated::class]);

    $company = Company::factory()->create();
    $location = StockLocation::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id]);

    $quant = StockQuant::factory()->create([
        'company_id' => $company->id,
        'location_id' => $location->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
    ]);

    Event::assertDispatched(ProductStockUpdated::class, function ($event) use ($product) {
        return $event->productId === $product->id && (float) $event->availableQuantity === 10.0;
    });

    $quant->update(['quantity' => 15]);

    Event::assertDispatched(ProductStockUpdated::class, function ($event) use ($product) {
        return $event->productId === $product->id && (float) $event->availableQuantity === 15.0;
    });
});

it('dispatches ProductStockUpdated event when StockQuant is deleted', function () {
    Event::fake([ProductStockUpdated::class]);

    $company = Company::factory()->create();
    $location = StockLocation::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id]);

    $quant = StockQuant::factory()->create([
        'company_id' => $company->id,
        'location_id' => $location->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    $quant->delete();

    Event::assertDispatched(ProductStockUpdated::class, function ($event) use ($product) {
        return $event->productId === $product->id;
    });
});
