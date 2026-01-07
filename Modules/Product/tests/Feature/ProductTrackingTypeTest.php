<?php

namespace Modules\Product\Tests\Feature;

use Modules\Inventory\Enums\Inventory\TrackingType;
use Modules\Inventory\Models\StockLocation;
use Modules\Inventory\Models\StockMove;
use Modules\Product\Models\Product;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->company = createCompany();
    $this->user = createUser($this->company);
    actingAsUser($this->user, $this->company);
});

it('allows setting tracking type on new product', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Serial,
    ]);

    expect($product->tracking_type)->toBe(TrackingType::Serial);
});

it('allows changing tracking type before stock moves', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::None,
    ]);

    $product->update(['tracking_type' => TrackingType::Serial]);

    expect($product->fresh()->tracking_type)->toBe(TrackingType::Serial);
});

it('prevents changing tracking type after stock moves exist', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Serial,
    ]);

    // Create a stock move
    $sourceLocation = StockLocation::factory()->for($this->company)->create();
    $destLocation = StockLocation::factory()->for($this->company)->create();

    StockMove::factory()->for($this->company)->create([
        'product_id' => $product->id,
        'source_location_id' => $sourceLocation->id,
        'destination_location_id' => $destLocation->id,
        'quantity' => 1,
    ]);

    expect($product->hasStockMoves())->toBeTrue();
});

it('correctly identifies products with no stock moves', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::None,
    ]);

    expect($product->hasStockMoves())->toBeFalse();
});

it('defaults to None tracking type if not specified', function () {
    $product = Product::factory()->for($this->company)->create();

    expect($product->tracking_type)->toBe(TrackingType::None);
});

it('maintains tracking type through product updates', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Lot,
    ]);

    $product->update(['name' => 'Updated Product Name']);

    expect($product->fresh()->tracking_type)->toBe(TrackingType::Lot);
});
