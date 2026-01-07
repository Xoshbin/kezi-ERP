<?php

namespace Modules\Inventory\Tests\Feature\Inventory;

use Modules\Foundation\Models\Partner;
use Modules\Inventory\Enums\Inventory\SerialNumberStatus;
use Modules\Inventory\Enums\Inventory\TrackingType;
use Modules\Inventory\Models\SerialNumber;
use Modules\Inventory\Models\StockLocation;
use Modules\Inventory\Services\Inventory\SerialNumberService;
use Modules\Product\Models\Product;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->company = createCompany();
    $this->user = createUser($this->company);
    actingAsUser($this->user, $this->company);
});

it('creates serial number with valid data', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Serial,
    ]);

    $location = StockLocation::factory()->for($this->company)->create();

    $service = app(SerialNumberService::class);
    $serial = $service->create(new \Modules\Inventory\DataTransferObjects\Inventory\CreateSerialNumberDTO(
        company_id: $this->company->id,
        product_id: $product->id,
        serial_code: 'SN-TEST-001',
        current_location_id: $location->id,
        warranty_end: now()->addYear(),
    ));

    expect($serial)
        ->toBeInstanceOf(SerialNumber::class)
        ->serial_code->toBe('SN-TEST-001')
        ->product_id->toBe($product->id)
        ->status->toBe(SerialNumberStatus::Available)
        ->current_location_id->toBe($location->id);

    expect($serial->isUnderWarranty())->toBeTrue();
});

it('enforces unique serial codes per product per company', function () {
    $product = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Serial,
    ]);

    SerialNumber::factory()->for($this->company)->for($product)->create([
        'serial_code' => 'SN-DUPLICATE',
    ]);

    expect(fn () => SerialNumber::factory()->for($this->company)->for($product)->create([
        'serial_code' => 'SN-DUPLICATE',
    ]))->toThrow(\Exception::class);
});

it('allows same serial code for different products', function () {
    $product1 = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Serial,
    ]);
    $product2 = Product::factory()->for($this->company)->create([
        'tracking_type' => TrackingType::Serial,
    ]);

    $serial1 = SerialNumber::factory()->for($this->company)->for($product1)->create([
        'serial_code' => 'SN-SHARED',
    ]);

    $serial2 = SerialNumber::factory()->for($this->company)->for($product2)->create([
        'serial_code' => 'SN-SHARED',
    ]);

    expect($serial1->serial_code)->toBe($serial2->serial_code)
        ->and($serial1->product_id)->not->toBe($serial2->product_id);
});

it('marks serial number as sold with customer information', function () {
    $serial = SerialNumber::factory()
        ->for($this->company)
        ->for(Product::factory()->for($this->company)->create(['tracking_type' => TrackingType::Serial]))
        ->create(['status' => SerialNumberStatus::Available]);

    $customer = Partner::factory()->for($this->company)->create();

    $service = app(SerialNumberService::class);
    $service->markSold($serial, $customer);

    expect($serial->fresh())
        ->status->toBe(SerialNumberStatus::Sold)
        ->sold_to_partner_id->toBe($customer->id)
        ->sold_at->not->toBeNull();
});

it('marks serial number as returned', function () {
    $serial = SerialNumber::factory()
        ->for($this->company)
        ->for(Product::factory()->for($this->company)->create(['tracking_type' => TrackingType::Serial]))
        ->sold()
        ->create();

    $service = app(SerialNumberService::class);
    $service->markReturned($serial);

    expect($serial->fresh()->status)->toBe(SerialNumberStatus::Returned);
});

it('marks serial number as defective with notes', function () {
    $serial = SerialNumber::factory()
        ->for($this->company)
        ->for(Product::factory()->for($this->company)->create(['tracking_type' => TrackingType::Serial]))
        ->create(['status' => SerialNumberStatus::Available]);

    $service = app(SerialNumberService::class);
    $service->markDefective($serial, 'Screen broken during testing');

    expect($serial->fresh())
        ->status->toBe(SerialNumberStatus::Defective)
        ->notes->toContain('Screen broken');
});

it('gets available serial numbers at location', function () {
    $product = Product::factory()->for($this->company)->create(['tracking_type' => TrackingType::Serial]);
    $location = StockLocation::factory()->for($this->company)->create();

    SerialNumber::factory()->for($this->company)->for($product)->count(3)->create([
        'status' => SerialNumberStatus::Available,
        'current_location_id' => $location->id,
    ]);

    SerialNumber::factory()->for($this->company)->for($product)->create([
        'status' => SerialNumberStatus::Sold,
        'current_location_id' => $location->id,
    ]);

    $service = app(SerialNumberService::class);
    $available = $service->getAvailableAtLocation($product, $location);

    expect($available)->toHaveCount(3);
});

it('correctly identifies warranty status', function () {
    $underWarranty = SerialNumber::factory()
        ->for($this->company)
        ->for(Product::factory()->for($this->company)->create(['tracking_type' => TrackingType::Serial]))
        ->withWarranty(12)
        ->create();

    $expired = SerialNumber::factory()
        ->for($this->company)
        ->for(Product::factory()->for($this->company)->create(['tracking_type' => TrackingType::Serial]))
        ->create([
            'warranty_start' => now()->subYears(2),
            'warranty_end' => now()->subYear(),
        ]);

    expect($underWarranty->isUnderWarranty())->toBeTrue()
        ->and($expired->isUnderWarranty())->toBeFalse();
});

it('finds serials with warranty expiring soon', function () {
    $product = Product::factory()->for($this->company)->create(['tracking_type' => TrackingType::Serial]);

    // Expiring in 15 days
    SerialNumber::factory()->for($this->company)->for($product)->create([
        'warranty_end' => now()->addDays(15),
    ]);

    // Expiring in 45 days
    SerialNumber::factory()->for($this->company)->for($product)->create([
        'warranty_end' => now()->addDays(45),
    ]);

    $service = app(SerialNumberService::class);
    $expiringSoon = $service->getWarrantyExpiringWithinDays(30, $this->company->id);

    expect($expiringSoon)->toHaveCount(1);
});
