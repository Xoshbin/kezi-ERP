<?php

namespace Jmeryar\Inventory\Tests\Feature\Inventory;

use Jmeryar\Inventory\Enums\Inventory\TrackingType;
use Jmeryar\Inventory\Models\SerialNumber;
use Jmeryar\Inventory\Models\StockLocation;
use Jmeryar\Inventory\Models\StockQuant;
use Jmeryar\Inventory\Services\Inventory\StockQuantService;
use Jmeryar\Product\Models\Product;
use RuntimeException;
use Tests\TestCase;

// uses(TestCase::class); // Removed redundant line

beforeEach(function () {
    $this->company = \App\Models\Company::factory()->create();
    $this->user = \App\Models\User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->actingAs($this->user);

    $this->service = app(StockQuantService::class);
    $this->location = StockLocation::factory()->for($this->company)->create();
});

it('enforces quantity equals 1 for serial tracked products', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Serial,
    ]);

    $serial = SerialNumber::factory()->for($this->company)->for($product)->create([
        'current_location_id' => $this->location->id,
    ]);

    // This should work - quantity = 1
    $quant = $this->service->adjust(
        companyId: $this->company->id,
        productId: $product->id,
        locationId: $this->location->id,
        deltaQty: 1,
        serialNumberId: $serial->id
    );

    expect($quant->quantity)->toBe(1.0);
});

it('throws exception when trying to set quantity greater than 1 for serial tracked product', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Serial,
    ]);

    $serial = SerialNumber::factory()->for($this->company)->for($product)->create([
        'current_location_id' => $this->location->id,
    ]);

    // Create initial quant with quantity 1
    $this->service->adjust(
        companyId: $this->company->id,
        productId: $product->id,
        locationId: $this->location->id,
        deltaQty: 1,
        serialNumberId: $serial->id
    );

    // This should throw - trying to increase to quantity > 1
    expect(fn () => $this->service->adjust(
        companyId: $this->company->id,
        productId: $product->id,
        locationId: $this->location->id,
        deltaQty: 1,
        serialNumberId: $serial->id
    ))->toThrow(RuntimeException::class, 'Serial-tracked product quantity cannot exceed 1');
});

it('allows negative adjustments for serial tracked products', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Serial,
    ]);

    $serial = SerialNumber::factory()->for($this->company)->for($product)->create([
        'current_location_id' => $this->location->id,
    ]);

    // Add 1
    $this->service->adjust(
        companyId: $this->company->id,
        productId: $product->id,
        locationId: $this->location->id,
        deltaQty: 1,
        serialNumberId: $serial->id
    );

    // Remove 1
    $quant = $this->service->adjust(
        companyId: $this->company->id,
        productId: $product->id,
        locationId: $this->location->id,
        deltaQty: -1,
        serialNumberId: $serial->id
    );

    expect($quant->quantity)->toBe(0.0);
});

it('creates separate quants for different serial numbers', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Serial,
    ]);

    $serial1 = SerialNumber::factory()->for($this->company)->for($product)->create();
    $serial2 = SerialNumber::factory()->for($this->company)->for($product)->create();

    $this->service->adjust(
        companyId: $this->company->id,
        productId: $product->id,
        locationId: $this->location->id,
        deltaQty: 1,
        serialNumberId: $serial1->id
    );

    $this->service->adjust(
        companyId: $this->company->id,
        productId: $product->id,
        locationId: $this->location->id,
        deltaQty: 1,
        serialNumberId: $serial2->id
    );

    $quants = StockQuant::where('company_id', $this->company->id)
        ->where('product_id', $product->id)
        ->where('location_id', $this->location->id)
        ->get();

    expect($quants)->toHaveCount(2)
        ->and($quants->sum('quantity'))->toBe(2.0);
});

it('reserves serial tracked quant', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Serial,
    ]);

    $serial = SerialNumber::factory()->for($this->company)->for($product)->create();

    $this->service->adjust(
        companyId: $this->company->id,
        productId: $product->id,
        locationId: $this->location->id,
        deltaQty: 1,
        serialNumberId: $serial->id
    );

    $quant = $this->service->reserve(
        companyId: $this->company->id,
        productId: $product->id,
        locationId: $this->location->id,
        qty: 1,
        serialNumberId: $serial->id
    );

    expect($quant->reserved_quantity)->toBe(1.0)
        ->and($quant->quantity)->toBe(1.0);
});

it('unreserves serial tracked quant', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Serial,
    ]);

    $serial = SerialNumber::factory()->for($this->company)->for($product)->create();

    $this->service->adjust(
        companyId: $this->company->id,
        productId: $product->id,
        locationId: $this->location->id,
        deltaQty: 1,
        serialNumberId: $serial->id
    );

    $this->service->reserve(
        companyId: $this->company->id,
        productId: $product->id,
        locationId: $this->location->id,
        qty: 1,
        serialNumberId: $serial->id
    );

    $quant = $this->service->unreserve(
        companyId: $this->company->id,
        productId: $product->id,
        locationId: $this->location->id,
        qty: 1,
        serialNumberId: $serial->id
    );

    expect($quant->reserved_quantity)->toBe(0.0);
});

it('allows regular quantity management for non-serial products', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::None,
    ]);

    // Non-serial products can have any quantity
    $quant = $this->service->adjust(
        companyId: $this->company->id,
        productId: $product->id,
        locationId: $this->location->id,
        deltaQty: 100
    );

    expect($quant->quantity)->toBe(100.0);
});
