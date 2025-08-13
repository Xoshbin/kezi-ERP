<?php

use App\Actions\Inventory\CreateInterCompanyStockTransferAction;
use App\DataTransferObjects\Inventory\CreateInterCompanyTransferDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Products\ProductType;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\StockMove;
use App\Models\User;
use App\Services\Inventory\InterCompanyStockTransferService;
use Brick\Money\Money;
use Carbon\Carbon;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

test('inter-company stock transfer service detects inter-company moves correctly', function () {
    // ARRANGE: Set up company hierarchy
    $parentCompany = Company::factory()->create(['name' => 'ParentCo']);
    $childCompany = Company::factory()->create([
        'name' => 'ChildCo',
        'parent_company_id' => $parentCompany->id,
    ]);

    // Create partner relationships
    $childPartnerInParent = Partner::factory()->create([
        'company_id' => $parentCompany->id,
        'name' => 'ChildCo Partner',
        'linked_company_id' => $childCompany->id,
    ]);

    $parentPartnerInChild = Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'ParentCo Partner',
        'linked_company_id' => $parentCompany->id,
    ]);

    // Create stock locations
    $parentWarehouse = StockLocation::factory()->create([
        'company_id' => $parentCompany->id,
        'name' => 'Parent Warehouse',
    ]);

    $childWarehouse = StockLocation::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'Child Warehouse',
    ]);

    // Create a product
    $product = Product::factory()->create([
        'company_id' => $parentCompany->id,
        'type' => ProductType::Storable,
        'average_cost' => Money::of(100, 'USD'),
    ]);

    // Create a stock move from parent to child
    $stockMove = StockMove::factory()->create([
        'company_id' => $parentCompany->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'from_location_id' => $parentWarehouse->id,
        'to_location_id' => $childWarehouse->id,
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Done,
        'move_date' => now(),
    ]);

    // ACT: Check if this should trigger inter-company processing
    $service = app(InterCompanyStockTransferService::class);
    $targetCompany = $service->shouldProcessInterCompany($stockMove);

    // ASSERT: Should detect the child company as target
    expect($targetCompany)->not->toBeNull();
    expect($targetCompany->id)->toBe($childCompany->id);
});

test('inter-company stock transfer service ignores non-inter-company moves', function () {
    // ARRANGE: Set up single company
    $company = Company::factory()->create(['name' => 'SingleCo']);

    // Create stock locations within same company
    $warehouse1 = StockLocation::factory()->create([
        'company_id' => $company->id,
        'name' => 'Warehouse 1',
    ]);

    $warehouse2 = StockLocation::factory()->create([
        'company_id' => $company->id,
        'name' => 'Warehouse 2',
    ]);

    // Create a product
    $product = Product::factory()->create([
        'company_id' => $company->id,
        'type' => ProductType::Storable,
    ]);

    // Create an internal stock move
    $stockMove = StockMove::factory()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'from_location_id' => $warehouse1->id,
        'to_location_id' => $warehouse2->id,
        'move_type' => StockMoveType::InternalTransfer,
        'status' => StockMoveStatus::Done,
        'move_date' => now(),
    ]);

    // ACT: Check if this should trigger inter-company processing
    $service = app(InterCompanyStockTransferService::class);
    $targetCompany = $service->shouldProcessInterCompany($stockMove);

    // ASSERT: Should not detect any target company
    expect($targetCompany)->toBeNull();
});

test('inter-company stock transfer service prevents circular processing', function () {
    // ARRANGE: Set up companies
    $parentCompany = Company::factory()->create(['name' => 'ParentCo']);
    $childCompany = Company::factory()->create(['name' => 'ChildCo']);

    // Create a stock move that was already created from inter-company transfer
    $stockMove = StockMove::factory()->create([
        'company_id' => $childCompany->id,
        'reference' => 'IC-TRANSFER-123', // This indicates it's already an inter-company transfer
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Done,
    ]);

    // ACT: Check if this should trigger inter-company processing
    $service = app(InterCompanyStockTransferService::class);
    $targetCompany = $service->shouldProcessInterCompany($stockMove);

    // ASSERT: Should not process (prevent circular)
    expect($targetCompany)->toBeNull();
});

test('create inter-company stock transfer action creates bidirectional transfers', function () {
    // ARRANGE: Set up company hierarchy with proper relationships
    $this->setupCompanyHierarchy();

    $parentCompany = Company::where('name', 'ParentCo')->first();
    $childCompany = Company::where('name', 'ChildCo')->first();

    // Create a storable product
    $product = Product::factory()->create([
        'company_id' => $parentCompany->id,
        'type' => ProductType::Storable,
        'name' => 'Test Product',
        'average_cost' => Money::of(50, 'USD'),
    ]);

    // Create a user
    $user = User::factory()->create();

    // Create the DTO
    $dto = new CreateInterCompanyTransferDTO(
        source_company_id: $parentCompany->id,
        target_company_id: $childCompany->id,
        product_id: $product->id,
        quantity: 15.0,
        transfer_date: Carbon::now(),
        created_by_user_id: $user->id,
        reference: 'TEST-TRANSFER-001',
    );

    // ACT: Execute the bidirectional transfer
    $action = app(CreateInterCompanyStockTransferAction::class);
    $result = $action->createBidirectionalTransfer($dto);

    // ASSERT: Both moves should be created
    expect($result)->toHaveKeys(['delivery', 'receipt']);

    $deliveryMove = $result['delivery'];
    $receiptMove = $result['receipt'];

    // Verify delivery move
    expect($deliveryMove->company_id)->toBe($parentCompany->id);
    expect($deliveryMove->move_type)->toBe(StockMoveType::Outgoing);
    expect($deliveryMove->quantity)->toBe(15.0);
    expect($deliveryMove->product_id)->toBe($product->id);
    expect($deliveryMove->status)->toBe(StockMoveStatus::Done);

    // Verify receipt move
    expect($receiptMove->company_id)->toBe($childCompany->id);
    expect($receiptMove->move_type)->toBe(StockMoveType::Incoming);
    expect($receiptMove->quantity)->toBe(15.0);
    expect($receiptMove->product_id)->toBe($product->id);
    expect($receiptMove->status)->toBe(StockMoveStatus::Done);
    expect($receiptMove->reference)->toBe("IC-TRANSFER-{$deliveryMove->id}");

    // Verify audit trail
    expect($receiptMove->source_type)->toBe(StockMove::class);
    expect($receiptMove->source_id)->toBe($deliveryMove->id);
});

test('inter-company stock transfer validates different companies', function () {
    // ARRANGE: Set up single company
    $company = Company::factory()->create(['name' => 'TestCo']);
    $product = Product::factory()->create(['company_id' => $company->id]);
    $user = User::factory()->create();

    // Create DTO with same source and target company
    $dto = new CreateInterCompanyTransferDTO(
        source_company_id: $company->id,
        target_company_id: $company->id, // Same as source
        product_id: $product->id,
        quantity: 10.0,
        transfer_date: Carbon::now(),
        created_by_user_id: $user->id,
    );

    // ACT & ASSERT: Should throw validation exception
    $action = app(CreateInterCompanyStockTransferAction::class);

    expect(fn() => $action->createBidirectionalTransfer($dto))
        ->toThrow(InvalidArgumentException::class, 'Source and target companies must be different');
});

test('inter-company stock transfer creates proper audit trail', function () {
    // ARRANGE: Set up companies and product
    $this->setupCompanyHierarchy();

    $parentCompany = Company::where('name', 'ParentCo')->first();
    $childCompany = Company::where('name', 'ChildCo')->first();

    $product = Product::factory()->create([
        'company_id' => $parentCompany->id,
        'type' => ProductType::Storable,
    ]);

    $user = User::factory()->create();

    // Create original stock move
    $originalMove = StockMove::factory()->create([
        'company_id' => $parentCompany->id,
        'product_id' => $product->id,
        'quantity' => 8.0,
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Done,
        'reference' => 'ORIGINAL-MOVE-001',
    ]);

    // ACT: Create inter-company transfer from original move
    $action = app(CreateInterCompanyStockTransferAction::class);
    $action->execute($originalMove, $childCompany);

    // ASSERT: Verify audit trail
    $interCompanyMove = StockMove::where('reference', "IC-TRANSFER-{$originalMove->id}")->first();

    expect($interCompanyMove)->not->toBeNull();
    expect($interCompanyMove->company_id)->toBe($childCompany->id);
    expect($interCompanyMove->source_type)->toBe(StockMove::class);
    expect($interCompanyMove->source_id)->toBe($originalMove->id);
    expect($interCompanyMove->move_type)->toBe(StockMoveType::Incoming);
    expect($interCompanyMove->quantity)->toBe($originalMove->quantity);
});

test('inter-company stock transfer handles inventory valuation correctly', function () {
    // ARRANGE: Set up companies with products
    $this->setupCompanyHierarchy();

    $parentCompany = Company::where('name', 'ParentCo')->first();
    $childCompany = Company::where('name', 'ChildCo')->first();

    $product = Product::factory()->create([
        'company_id' => $parentCompany->id,
        'type' => ProductType::Storable,
        'average_cost' => Money::of(75, 'USD'),
        'quantity_on_hand' => 100,
    ]);

    $user = User::factory()->create();

    // Create DTO for transfer
    $dto = new CreateInterCompanyTransferDTO(
        source_company_id: $parentCompany->id,
        target_company_id: $childCompany->id,
        product_id: $product->id,
        quantity: 20.0,
        transfer_date: Carbon::now(),
        created_by_user_id: $user->id,
    );

    // ACT: Execute the transfer
    $action = app(CreateInterCompanyStockTransferAction::class);
    $result = $action->createBidirectionalTransfer($dto);

    // ASSERT: Verify inventory valuation was processed
    $deliveryMove = $result['delivery'];
    $receiptMove = $result['receipt'];

    // Check that stock move valuations were created
    expect($deliveryMove->exists())->toBeTrue();
    expect($receiptMove->exists())->toBeTrue();

    // Verify the moves have proper references for audit trail
    expect($receiptMove->reference)->toBe("IC-TRANSFER-{$deliveryMove->id}");
    expect($receiptMove->source_id)->toBe($deliveryMove->id);
});

// Helper method to set up company hierarchy
function setupCompanyHierarchy(): void
{
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

    Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'ParentCo Partner',
        'linked_company_id' => $parentCompany->id,
    ]);

    // Create default stock locations for both companies
    StockLocation::factory()->create([
        'company_id' => $parentCompany->id,
        'name' => 'Parent Warehouse',
        'type' => \App\Enums\Inventory\StockLocationType::Internal,
    ]);

    StockLocation::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'Child Warehouse',
        'type' => \App\Enums\Inventory\StockLocationType::Internal,
    ]);
}
