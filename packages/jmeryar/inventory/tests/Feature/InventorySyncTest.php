<?php

namespace Jmeryar\Inventory\tests\Feature;

use App\Models\Company;
use Jmeryar\Inventory\Models\StockLocation;
use Jmeryar\Inventory\Models\StockQuant;
use Jmeryar\Product\Models\Product;

it('updates product quantity on hand when stock quant is created', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->create(['company_id' => $company->id]);
    $location = StockLocation::factory()->create(['company_id' => $company->id]);

    // Initial check
    expect($product->refresh()->quantity_on_hand)->toBe(0.0);

    // Create StockQuant
    StockQuant::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'location_id' => $location->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
    ]);

    // Check if product quantity updated
    expect($product->refresh()->quantity_on_hand)->toBe(10.0);
});

it('updates product quantity on hand when stock quant is updated', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->create(['company_id' => $company->id]);
    $location = StockLocation::factory()->create(['company_id' => $company->id]);

    $quant = StockQuant::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'location_id' => $location->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
    ]);

    // Check initial update
    expect($product->refresh()->quantity_on_hand)->toBe(10.0);

    // Update Quant
    $quant->update(['quantity' => 25]);

    // Check updated quantity
    expect($product->refresh()->quantity_on_hand)->toBe(25.0);
});
