<?php

use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Products\ProductType;
use App\Filament\Clusters\Inventory\Resources\InterCompanyStockTransferResource;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\StockMove;
use App\Models\User;
use Filament\Actions\CreateAction;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('inter-company stock transfer resource can list transfers', function () {
    // ARRANGE: Create inter-company stock transfers
    $parentCompany = Company::factory()->create(['name' => 'ParentCo']);
    $childCompany = Company::factory()->create(['name' => 'ChildCo']);

    $product = Product::factory()->create([
        'company_id' => $parentCompany->id,
        'type' => ProductType::Storable,
    ]);

    // Create some inter-company transfers
    $transfer1 = StockMove::factory()->create([
        'company_id' => $parentCompany->id,
        'product_id' => $product->id,
        'reference' => 'IC-TRANSFER-123',
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Done,
        'quantity' => 10,
    ]);

    $transfer2 = StockMove::factory()->create([
        'company_id' => $childCompany->id,
        'product_id' => $product->id,
        'reference' => 'IC-TRANSFER-456',
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Done,
        'quantity' => 5,
    ]);

    // Create a non-inter-company transfer (should not appear)
    StockMove::factory()->create([
        'company_id' => $parentCompany->id,
        'product_id' => $product->id,
        'reference' => 'REGULAR-MOVE-789',
        'move_type' => StockMoveType::InternalTransfer,
        'status' => StockMoveStatus::Done,
    ]);

    // ACT & ASSERT: Test the list page
    Livewire::test(InterCompanyStockTransferResource\Pages\ListInterCompanyStockTransfers::class)
        ->assertCanSeeTableRecords([$transfer1, $transfer2])
        ->assertCountTableRecords(2); // Only inter-company transfers should be visible
});

test('inter-company stock transfer resource can view transfer details', function () {
    // ARRANGE: Create an inter-company transfer
    $parentCompany = Company::factory()->create(['name' => 'ParentCo']);
    $product = Product::factory()->create([
        'company_id' => $parentCompany->id,
        'type' => ProductType::Storable,
        'name' => 'Test Product',
    ]);

    $fromLocation = StockLocation::factory()->create([
        'company_id' => $parentCompany->id,
        'name' => 'Warehouse A',
    ]);

    $toLocation = StockLocation::factory()->create([
        'company_id' => $parentCompany->id,
        'name' => 'Customer Location',
    ]);

    $transfer = StockMove::factory()->create([
        'company_id' => $parentCompany->id,
        'product_id' => $product->id,
        'from_location_id' => $fromLocation->id,
        'to_location_id' => $toLocation->id,
        'reference' => 'IC-TRANSFER-999',
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Done,
        'quantity' => 15.5,
        'move_date' => now()->subDays(2),
    ]);

    // ACT & ASSERT: Test the view page
    Livewire::test(InterCompanyStockTransferResource\Pages\ViewInterCompanyStockTransfer::class, [
        'record' => $transfer->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSeeText('IC-TRANSFER-999')
        ->assertSeeText('Test Product')
        ->assertSeeText('15.5')
        ->assertSeeText('Warehouse A')
        ->assertSeeText('Customer Location')
        ->assertSeeText('ParentCo');
});

test('inter-company stock transfer resource can create bidirectional transfer', function () {
    // ARRANGE: Set up company hierarchy
    $parentCompany = Company::factory()->create(['name' => 'ParentCo']);
    $childCompany = Company::factory()->create([
        'name' => 'ChildCo',
        'parent_company_id' => $parentCompany->id,
    ]);

    // Create partner relationships
    Partner::factory()->create([
        'company_id' => $parentCompany->id,
        'name' => 'ChildCo Partner',
        'linked_company_id' => $childCompany->id,
    ]);

    // Create a storable product
    $product = Product::factory()->create([
        'company_id' => $parentCompany->id,
        'type' => ProductType::Storable,
        'name' => 'Transfer Product',
    ]);

    // Create stock locations
    StockLocation::factory()->create([
        'company_id' => $parentCompany->id,
        'name' => 'Parent Warehouse',
    ]);

    StockLocation::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'Child Warehouse',
    ]);

    // ACT: Test creating a transfer through the UI
    Livewire::test(InterCompanyStockTransferResource\Pages\ListInterCompanyStockTransfers::class)
        ->callAction(CreateAction::class, data: [
            'source_company_id' => $parentCompany->id,
            'target_company_id' => $childCompany->id,
            'product_id' => $product->id,
            'quantity' => 25.0,
            'transfer_date' => now()->format('Y-m-d'),
            'reference' => 'TEST-UI-TRANSFER',
            'notes' => 'Test transfer via UI',
        ])
        ->assertHasNoActionErrors();

    // ASSERT: Verify both moves were created
    $deliveryMove = StockMove::where('company_id', $parentCompany->id)
        ->where('move_type', StockMoveType::Outgoing)
        ->where('product_id', $product->id)
        ->where('quantity', 25.0)
        ->first();

    expect($deliveryMove)->not->toBeNull();
    expect($deliveryMove->status)->toBe(StockMoveStatus::Done);

    $receiptMove = StockMove::where('reference', "IC-TRANSFER-{$deliveryMove->id}")->first();
    expect($receiptMove)->not->toBeNull();
    expect($receiptMove->company_id)->toBe($childCompany->id);
    expect($receiptMove->move_type)->toBe(StockMoveType::Incoming);
    expect($receiptMove->quantity)->toBe(25.0);
});

test('inter-company stock transfer resource validates form data', function () {
    // ARRANGE: Set up minimal company
    $company = Company::factory()->create(['name' => 'TestCo']);

    // ACT & ASSERT: Test form validation
    Livewire::test(InterCompanyStockTransferResource\Pages\ListInterCompanyStockTransfers::class)
        ->callAction(CreateAction::class, data: [
            'source_company_id' => $company->id,
            'target_company_id' => $company->id, // Same as source - should be invalid
            'product_id' => null, // Missing required field
            'quantity' => -5, // Invalid quantity
            'transfer_date' => '', // Missing required field
        ])
        ->assertHasActionErrors([
            'product_id',
            'quantity',
            'transfer_date',
        ]);
});

test('inter-company stock transfer resource filters work correctly', function () {
    // ARRANGE: Create transfers for different companies
    $company1 = Company::factory()->create(['name' => 'Company1']);
    $company2 = Company::factory()->create(['name' => 'Company2']);

    $product = Product::factory()->create(['company_id' => $company1->id]);

    $transfer1 = StockMove::factory()->create([
        'company_id' => $company1->id,
        'product_id' => $product->id,
        'reference' => 'IC-TRANSFER-111',
        'move_type' => StockMoveType::Outgoing,
        'move_date' => now()->subDays(5),
    ]);

    $transfer2 = StockMove::factory()->create([
        'company_id' => $company2->id,
        'product_id' => $product->id,
        'reference' => 'IC-TRANSFER-222',
        'move_type' => StockMoveType::Incoming,
        'move_date' => now()->subDays(3),
    ]);

    // ACT & ASSERT: Test company filter
    Livewire::test(InterCompanyStockTransferResource\Pages\ListInterCompanyStockTransfers::class)
        ->filterTable('company_id', $company1->id)
        ->assertCanSeeTableRecords([$transfer1])
        ->assertCanNotSeeTableRecords([$transfer2]);

    // Test move type filter
    Livewire::test(InterCompanyStockTransferResource\Pages\ListInterCompanyStockTransfers::class)
        ->filterTable('move_type', StockMoveType::Incoming->value)
        ->assertCanSeeTableRecords([$transfer2])
        ->assertCanNotSeeTableRecords([$transfer1]);
});

test('inter-company stock transfer resource shows related transfers', function () {
    // ARRANGE: Create a bidirectional transfer
    $parentCompany = Company::factory()->create(['name' => 'ParentCo']);
    $childCompany = Company::factory()->create(['name' => 'ChildCo']);
    $product = Product::factory()->create(['company_id' => $parentCompany->id]);

    // Create delivery move
    $deliveryMove = StockMove::factory()->create([
        'company_id' => $parentCompany->id,
        'product_id' => $product->id,
        'reference' => 'DELIVERY-001',
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Done,
    ]);

    // Create corresponding receipt move
    $receiptMove = StockMove::factory()->create([
        'company_id' => $childCompany->id,
        'product_id' => $product->id,
        'reference' => "IC-TRANSFER-{$deliveryMove->id}",
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Done,
        'source_type' => StockMove::class,
        'source_id' => $deliveryMove->id,
    ]);

    // ACT & ASSERT: Test viewing the receipt shows related delivery
    Livewire::test(InterCompanyStockTransferResource\Pages\ViewInterCompanyStockTransfer::class, [
        'record' => $receiptMove->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSeeText("Delivery #{$deliveryMove->id} in {$deliveryMove->company->name}");
});
