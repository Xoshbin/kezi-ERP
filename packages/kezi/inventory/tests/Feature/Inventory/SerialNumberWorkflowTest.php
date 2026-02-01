<?php

namespace Kezi\Inventory\Tests\Feature\Inventory;

use Kezi\Foundation\Models\Partner;
use Kezi\Inventory\Enums\Inventory\SerialNumberStatus;
use Kezi\Inventory\Enums\Inventory\TrackingType;
use Kezi\Inventory\Models\SerialNumber;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Services\Inventory\SerialNumberService;
use Kezi\Inventory\Services\Inventory\StockQuantService;
use Kezi\Product\Models\Product;
use Tests\TestCase;

// // uses(TestCase::class); // Removed redundant line // Removed redundant line

beforeEach(function () {
    $this->company = \App\Models\Company::factory()->create();
    $this->user = \App\Models\User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->actingAs($this->user);

    $this->serialService = app(SerialNumberService::class);
    $this->quantService = app(StockQuantService::class);
});

it('completes full workflow: create → receipt → sale → return', function () {
    // 1. Create serial-tracked product
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Serial,
        'name' => 'iPhone 15 Pro',
    ]);

    $warehouse = StockLocation::factory()->for($this->company)->create(['name' => 'Main Warehouse']);
    $customer = Partner::factory()->for($this->company)->create(['name' => 'Test Customer']);

    // 2. Create serial number on goods receipt
    $serial = $this->serialService->create(new \Kezi\Inventory\DataTransferObjects\Inventory\CreateSerialNumberDTO(
        company_id: $this->company->id,
        product_id: $product->id,
        serial_code: 'IPHONE-SN-12345',
        current_location_id: $warehouse->id,
        warranty_start: now(),
        warranty_end: now()->addYear(),
    ));

    expect($serial->status)->toBe(SerialNumberStatus::Available)
        ->and($serial->isUnderWarranty())->toBeTrue();

    // 3. Add to stock quant
    $quant = $this->quantService->adjust(
        companyId: $this->company->id,
        productId: $product->id,
        locationId: $warehouse->id,
        deltaQty: 1,
        serialNumberId: $serial->id
    );

    expect($quant->quantity)->toBe(1.0);

    // 4. Sell to customer
    $this->serialService->markSold($serial, $customer);

    expect($serial->fresh())
        ->status->toBe(SerialNumberStatus::Sold)
        ->sold_to_partner_id->toBe($customer->id)
        ->sold_at->not->toBeNull();

    // 5. Customer returns the item
    $this->serialService->markReturned($serial);

    expect($serial->fresh()->status)->toBe(SerialNumberStatus::Returned);

    // 6. Inspect and mark as defective
    $this->serialService->markDefective($serial, 'Customer reported battery issue');

    expect($serial->fresh())
        ->status->toBe(SerialNumberStatus::Defective)
        ->notes->toContain('battery issue');
});

it('handles multiple serial numbers for same product', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Serial,
        'name' => 'MacBook Pro',
    ]);

    $warehouse = StockLocation::factory()->for($this->company)->create();

    // Receive 3 units with different serial numbers
    $serials = collect(['MBP-001', 'MBP-002', 'MBP-003'])->map(function ($code) use ($product, $warehouse) {
        return $this->serialService->create(new \Kezi\Inventory\DataTransferObjects\Inventory\CreateSerialNumberDTO(
            company_id: $this->company->id,
            product_id: $product->id,
            serial_code: $code,
            current_location_id: $warehouse->id,
        ));
    });

    // Add all to stock
    $serials->each(function ($serial) use ($product, $warehouse) {
        $this->quantService->adjust(
            companyId: $this->company->id,
            productId: $product->id,
            locationId: $warehouse->id,
            deltaQty: 1,
            serialNumberId: $serial->id
        );
    });

    // Get available serials at location
    $available = $this->serialService->getAvailableAtLocation($product, $warehouse);

    expect($available)->toHaveCount(3)
        ->and($available->pluck('serial_code')->sort()->values()->all())
        ->toBe(['MBP-001', 'MBP-002', 'MBP-003']);
});

it('tracks serial location through transfers', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Serial,
    ]);

    $warehouse = StockLocation::factory()->for($this->company)->create(['name' => 'Warehouse']);
    $shop = StockLocation::factory()->for($this->company)->create(['name' => 'Retail Shop']);

    $serial = $this->serialService->create(new \Kezi\Inventory\DataTransferObjects\Inventory\CreateSerialNumberDTO(
        company_id: $this->company->id,
        product_id: $product->id,
        serial_code: 'TRANSFER-TEST-001',
        current_location_id: $warehouse->id,
    ));

    expect($serial->current_location_id)->toBe($warehouse->id);

    // Transfer to shop
    $this->serialService->assignToLocation($serial, $shop);

    expect($serial->fresh()->current_location_id)->toBe($shop->id);
});

it('validates serial for outgoing shipment', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Serial,
    ]);

    $warehouse = StockLocation::factory()->for($this->company)->create();

    $availableSerial = SerialNumber::factory()
        ->for($this->company)
        ->for($product)
        ->available()
        ->create(['current_location_id' => $warehouse->id]);

    $soldSerial = SerialNumber::factory()
        ->for($this->company)
        ->for($product)
        ->sold()
        ->create(['current_location_id' => $warehouse->id]);

    $move = \Kezi\Inventory\Models\StockMove::factory()->for($this->company)->create([
        'product_id' => $product->id,
        'from_location_id' => $warehouse->id,
        'quantity' => 1,
    ]);

    expect($this->serialService->validateForOutgoing($availableSerial, $move))->toBeTrue()
        ->and($this->serialService->validateForOutgoing($soldSerial, $move))->toBeFalse();
});

it('finds warranty expiring within timeframe', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Serial,
    ]);

    // Create serials with various warranty end dates
    SerialNumber::factory()->for($this->company)->for($product)->create([
        'warranty_end' => now()->addDays(10), // Within 30 days
    ]);

    SerialNumber::factory()->for($this->company)->for($product)->create([
        'warranty_end' => now()->addDays(25), // Within 30 days
    ]);

    SerialNumber::factory()->for($this->company)->for($product)->create([
        'warranty_end' => now()->addDays(60), // Not within 30 days
    ]);

    SerialNumber::factory()->for($this->company)->for($product)->create([
        'warranty_end' => now()->subDays(10), // Already expired
    ]);

    $expiring = $this->serialService->getWarrantyExpiringWithinDays(30, $this->company->id);

    expect($expiring)->toHaveCount(2);
});
