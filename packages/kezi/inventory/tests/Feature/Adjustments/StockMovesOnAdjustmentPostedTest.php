<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Inventory\Enums\Adjustments\AdjustmentDocumentType;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Events\AdjustmentDocumentPosted;
use Kezi\Inventory\Models\AdjustmentDocument;
use Kezi\Inventory\Models\AdjustmentDocumentLine;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockMove;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

test('credit note with storable products creates stock move from customer to internal location', function () {
    // 1. Arrange - Set up inventory test environment
    $this->setupInventoryTestEnvironment();

    // Create a storable product
    $product = Product::factory()->for($this->company)->create([
        'type' => ProductType::Storable,
        'name' => 'Test Product',
    ]);

    // Create an adjustment document (credit note) with a product line
    $adjustment = AdjustmentDocument::factory()
        ->for($this->company)
        ->create([
            'currency_id' => $this->company->currency_id,
            'type' => AdjustmentDocumentType::CreditNote,
            'total_amount' => Money::of(100, $this->company->currency->code),
            'total_tax' => Money::of(0, $this->company->currency->code),
            'posted_at' => now(),
            'reference_number' => 'TEST-CN-SM-001',
        ]);

    // Add a line with a storable product
    AdjustmentDocumentLine::factory()
        ->for($adjustment, 'adjustmentDocument')
        ->for($this->company)
        ->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'description' => 'Returned product',
        ]);

    // 2. Act - Dispatch the event
    AdjustmentDocumentPosted::dispatch($adjustment);

    // 3. Assert - Stock move was created with correct direction
    $stockMove = StockMove::where('source_type', AdjustmentDocument::class)
        ->where('source_id', $adjustment->id)
        ->first();

    expect($stockMove)->not->toBeNull();
    expect($stockMove->move_type)->toBe(StockMoveType::Incoming);
    expect($stockMove->reference)->toBe($adjustment->reference_number);

    // Assert product line has correct from/to locations
    $productLine = $stockMove->productLines->first();
    expect($productLine)->not->toBeNull();
    expect($productLine->product_id)->toBe($product->id);
    expect($productLine->quantity)->toBe('5.0000');

    // From: Customer location, To: Internal location
    $fromLocation = StockLocation::find($productLine->from_location_id);
    $toLocation = StockLocation::find($productLine->to_location_id);
    expect($fromLocation->type)->toBe(StockLocationType::Customer);
    expect($toLocation->type)->toBe(StockLocationType::Internal);
});

test('debit note with storable products creates stock move from internal to vendor location', function () {
    // 1. Arrange - Set up inventory test environment
    $this->setupInventoryTestEnvironment();

    // Create a storable product
    $product = Product::factory()->for($this->company)->create([
        'type' => ProductType::Storable,
        'name' => 'Test Product',
    ]);

    // Create a vendor bill first
    $vendorBill = \Kezi\Purchase\Models\VendorBill::factory()
        ->for($this->company)
        ->create(['currency_id' => $this->company->currency_id]);

    // Create an adjustment document (debit note) with a product line
    $adjustment = AdjustmentDocument::factory()
        ->for($this->company)
        ->create([
            'currency_id' => $this->company->currency_id,
            'type' => AdjustmentDocumentType::DebitNote,
            'original_vendor_bill_id' => $vendorBill->id,
            'total_amount' => Money::of(200, $this->company->currency->code),
            'total_tax' => Money::of(0, $this->company->currency->code),
            'posted_at' => now(),
            'reference_number' => 'TEST-DN-SM-001',
        ]);

    // Add a line with a storable product
    AdjustmentDocumentLine::factory()
        ->for($adjustment, 'adjustmentDocument')
        ->for($this->company)
        ->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'description' => 'Returned to vendor',
        ]);

    $this->seedStock($product, $this->stockLocation, 10);

    // 2. Act - Dispatch the event
    AdjustmentDocumentPosted::dispatch($adjustment);

    // 3. Assert - Stock move was created with correct direction
    $stockMove = StockMove::where('source_type', AdjustmentDocument::class)
        ->where('source_id', $adjustment->id)
        ->first();

    expect($stockMove)->not->toBeNull();
    expect($stockMove->move_type)->toBe(StockMoveType::Outgoing);
    expect($stockMove->reference)->toBe($adjustment->reference_number);

    // Assert product line has correct from/to locations
    $productLine = $stockMove->productLines->first();
    expect($productLine)->not->toBeNull();
    expect($productLine->product_id)->toBe($product->id);
    expect($productLine->quantity)->toBe('3.0000');

    // From: Internal location, To: Vendor location
    $fromLocation = StockLocation::find($productLine->from_location_id);
    $toLocation = StockLocation::find($productLine->to_location_id);
    expect($fromLocation->type)->toBe(StockLocationType::Internal);
    expect($toLocation->type)->toBe(StockLocationType::Vendor);
});

test('adjustment document without storable products does not create stock move', function () {
    // 1. Arrange
    // Create a service product (not storable)
    $product = Product::factory()->for($this->company)->create([
        'type' => ProductType::Service,
        'name' => 'Service Product',
    ]);

    // Create an adjustment document
    $adjustment = AdjustmentDocument::factory()
        ->for($this->company)
        ->create([
            'currency_id' => $this->company->currency_id,
            'type' => AdjustmentDocumentType::CreditNote,
            'total_amount' => Money::of(50, $this->company->currency->code),
            'total_tax' => Money::of(0, $this->company->currency->code),
            'posted_at' => now(),
            'reference_number' => 'TEST-CN-SVC-001',
        ]);

    // Add a line with a service product
    AdjustmentDocumentLine::factory()
        ->for($adjustment, 'adjustmentDocument')
        ->for($this->company)
        ->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'description' => 'Consulting service refund',
        ]);

    // 2. Act - Dispatch the event
    AdjustmentDocumentPosted::dispatch($adjustment);

    // 3. Assert - No stock move was created
    $stockMoveCount = StockMove::where('source_type', AdjustmentDocument::class)
        ->where('source_id', $adjustment->id)
        ->count();

    expect($stockMoveCount)->toBe(0);
});
