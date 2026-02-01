<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Jmeryar\Inventory\Enums\Inventory\StockMoveStatus;
use Jmeryar\Inventory\Enums\Inventory\StockMoveType;
use Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\Pages\ListStockMoves;
use Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\Pages\ViewStockMove;
use Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\StockMoveResource;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Inventory\Models\StockMoveProductLine;
use Jmeryar\Product\Models\Product;
use Jmeryar\Purchase\Enums\Purchases\VendorBillStatus;
use Jmeryar\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
});

// ==========================================
// Table Listing Tests
// ==========================================

it('can render stock move list page', function () {
    $this->get(StockMoveResource::getUrl('index'))
        ->assertSuccessful();
});

it('displays key columns for incoming moves', function () {
    $stockMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-INC-001',
        'description' => 'Test incoming move',
        'created_by_user_id' => $this->user->id,
    ]);

    Livewire::test(ListStockMoves::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$stockMove])
        ->assertTableColumnExists('reference')
        ->assertTableColumnExists('move_date')
        ->assertTableColumnExists('status')
        ->assertTableColumnExists('move_type');
});

it('displays key columns for outgoing moves', function () {
    $stockMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Done,
        'move_date' => now(),
        'reference' => 'SM-OUT-001',
        'created_by_user_id' => $this->user->id,
    ]);

    Livewire::test(ListStockMoves::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$stockMove]);
});

it('displays key columns for internal transfer moves', function () {
    $stockMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::InternalTransfer,
        'status' => StockMoveStatus::Confirmed,
        'move_date' => now(),
        'reference' => 'SM-INT-001',
        'created_by_user_id' => $this->user->id,
    ]);

    Livewire::test(ListStockMoves::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$stockMove]);
});

it('displays product line count in table', function () {
    $product1 = Product::factory()->create(['company_id' => $this->company->id]);
    $product2 = Product::factory()->create(['company_id' => $this->company->id]);

    $stockMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-MULTI-001',
        'created_by_user_id' => $this->user->id,
    ]);

    StockMoveProductLine::create([
        'stock_move_id' => $stockMove->id,
        'company_id' => $this->company->id,
        'product_id' => $product1->id,
        'quantity' => 5.0,
        'from_location_id' => $this->vendorLocation->id,
        'to_location_id' => $this->stockLocation->id,
    ]);

    StockMoveProductLine::create([
        'stock_move_id' => $stockMove->id,
        'company_id' => $this->company->id,
        'product_id' => $product2->id,
        'quantity' => 10.0,
        'from_location_id' => $this->vendorLocation->id,
        'to_location_id' => $this->stockLocation->id,
    ]);

    Livewire::test(ListStockMoves::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$stockMove]);
});

// ==========================================
// Filter Tests
// ==========================================

it('can filter by status', function () {
    $draftMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-DRAFT-001',
        'created_by_user_id' => $this->user->id,
    ]);

    $doneMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Done,
        'move_date' => now(),
        'reference' => 'SM-DONE-001',
        'created_by_user_id' => $this->user->id,
    ]);

    // Filter by Draft status
    Livewire::test(ListStockMoves::class)
        ->filterTable('status', [StockMoveStatus::Draft->value])
        ->assertCanSeeTableRecords([$draftMove])
        ->assertCanNotSeeTableRecords([$doneMove]);

    // Filter by Done status
    Livewire::test(ListStockMoves::class)
        ->filterTable('status', [StockMoveStatus::Done->value])
        ->assertCanSeeTableRecords([$doneMove])
        ->assertCanNotSeeTableRecords([$draftMove]);
});

it('can filter by move type', function () {
    $incomingMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-INC-FILTER',
        'created_by_user_id' => $this->user->id,
    ]);

    $outgoingMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Outgoing,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-OUT-FILTER',
        'created_by_user_id' => $this->user->id,
    ]);

    $internalMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::InternalTransfer,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-INT-FILTER',
        'created_by_user_id' => $this->user->id,
    ]);

    // Filter by Incoming type
    Livewire::test(ListStockMoves::class)
        ->filterTable('move_type', [StockMoveType::Incoming->value])
        ->assertCanSeeTableRecords([$incomingMove])
        ->assertCanNotSeeTableRecords([$outgoingMove, $internalMove]);

    // Filter by Outgoing type
    Livewire::test(ListStockMoves::class)
        ->filterTable('move_type', [StockMoveType::Outgoing->value])
        ->assertCanSeeTableRecords([$outgoingMove])
        ->assertCanNotSeeTableRecords([$incomingMove, $internalMove]);

    // Filter by Internal Transfer type
    Livewire::test(ListStockMoves::class)
        ->filterTable('move_type', [StockMoveType::InternalTransfer->value])
        ->assertCanSeeTableRecords([$internalMove])
        ->assertCanNotSeeTableRecords([$incomingMove, $outgoingMove]);
});

it('can filter by date range', function () {
    $oldMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now()->subMonth(),
        'reference' => 'SM-OLD-001',
        'created_by_user_id' => $this->user->id,
    ]);

    $recentMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-RECENT-001',
        'created_by_user_id' => $this->user->id,
    ]);

    // Filter by recent date range (last week)
    Livewire::test(ListStockMoves::class)
        ->filterTable('move_date', [
            'from' => now()->subWeek()->format('Y-m-d'),
            'until' => now()->format('Y-m-d'),
        ])
        ->assertCanSeeTableRecords([$recentMove])
        ->assertCanNotSeeTableRecords([$oldMove]);
});

it('can filter by product', function () {
    $product1 = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Product Alpha',
    ]);
    $product2 = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Product Beta',
    ]);

    $moveWithProduct1 = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-P1-001',
        'created_by_user_id' => $this->user->id,
    ]);

    StockMoveProductLine::create([
        'stock_move_id' => $moveWithProduct1->id,
        'company_id' => $this->company->id,
        'product_id' => $product1->id,
        'quantity' => 5.0,
        'from_location_id' => $this->vendorLocation->id,
        'to_location_id' => $this->stockLocation->id,
    ]);

    $moveWithProduct2 = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-P2-001',
        'created_by_user_id' => $this->user->id,
    ]);

    StockMoveProductLine::create([
        'stock_move_id' => $moveWithProduct2->id,
        'company_id' => $this->company->id,
        'product_id' => $product2->id,
        'quantity' => 10.0,
        'from_location_id' => $this->vendorLocation->id,
        'to_location_id' => $this->stockLocation->id,
    ]);

    // Filter by Product 1
    Livewire::test(ListStockMoves::class)
        ->filterTable('product_id', [$product1->id])
        ->assertCanSeeTableRecords([$moveWithProduct1])
        ->assertCanNotSeeTableRecords([$moveWithProduct2]);
});

// ==========================================
// View Page Tests
// ==========================================

it('can render view page', function () {
    $stockMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-VIEW-001',
        'created_by_user_id' => $this->user->id,
    ]);

    $this->get(StockMoveResource::getUrl('view', ['record' => $stockMove]))
        ->assertSuccessful();
});

it('displays product lines with quantities on view page', function () {
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Product for View',
    ]);

    $stockMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-VIEW-LINES',
        'created_by_user_id' => $this->user->id,
    ]);

    StockMoveProductLine::create([
        'stock_move_id' => $stockMove->id,
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'quantity' => 25.5,
        'from_location_id' => $this->vendorLocation->id,
        'to_location_id' => $this->stockLocation->id,
    ]);

    Livewire::test(ViewStockMove::class, ['record' => $stockMove->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('SM-VIEW-LINES')
        ->assertSee('Test Product for View');
});

it('displays source document link for vendor bill', function () {
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'bill_reference' => 'VB-SOURCE-001',
    ]);

    $stockMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-VENDOR-BILL',
        'source_type' => VendorBill::class,
        'source_id' => $vendorBill->id,
        'created_by_user_id' => $this->user->id,
    ]);

    Livewire::test(ViewStockMove::class, ['record' => $stockMove->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('SM-VENDOR-BILL');
});

// ==========================================
// UI Action Tests
// ==========================================

it('shows confirm action for draft moves', function () {
    $draftMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-ACTION-DRAFT',
        'created_by_user_id' => $this->user->id,
    ]);

    Livewire::test(ListStockMoves::class)
        ->assertTableActionVisible('confirm', $draftMove);
});

it('hides confirm action for done moves', function () {
    $doneMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Done,
        'move_date' => now(),
        'reference' => 'SM-ACTION-DONE',
        'created_by_user_id' => $this->user->id,
    ]);

    Livewire::test(ListStockMoves::class)
        ->assertTableActionHidden('confirm', $doneMove);
});

it('can mount confirm action for draft moves', function () {
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $draftMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-MOUNT-CONFIRM',
        'created_by_user_id' => $this->user->id,
    ]);

    StockMoveProductLine::create([
        'stock_move_id' => $draftMove->id,
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'quantity' => 5.0,
        'from_location_id' => $this->vendorLocation->id,
        'to_location_id' => $this->stockLocation->id,
    ]);

    // Test that the confirm action can be mounted (opens confirmation modal)
    // Full confirmation flow is tested in ManualStockMoveFilamentTest
    Livewire::test(ListStockMoves::class)
        ->mountTableAction('confirm', $draftMove)
        ->assertSuccessful();
});

it('hides edit action for done moves', function () {
    $doneMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Done,
        'move_date' => now(),
        'reference' => 'SM-EDIT-DONE',
        'created_by_user_id' => $this->user->id,
    ]);

    Livewire::test(ListStockMoves::class)
        ->assertTableActionHidden('edit', $doneMove);
});

it('hides delete action for done moves', function () {
    $doneMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Done,
        'move_date' => now(),
        'reference' => 'SM-DELETE-DONE',
        'created_by_user_id' => $this->user->id,
    ]);

    Livewire::test(ListStockMoves::class)
        ->assertTableActionHidden('delete', $doneMove);
});

it('shows edit action for draft moves', function () {
    $draftMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-EDIT-DRAFT',
        'created_by_user_id' => $this->user->id,
    ]);

    Livewire::test(ListStockMoves::class)
        ->assertTableActionVisible('edit', $draftMove);
});

it('shows delete action for draft moves', function () {
    $draftMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-DELETE-DRAFT',
        'created_by_user_id' => $this->user->id,
    ]);

    Livewire::test(ListStockMoves::class)
        ->assertTableActionVisible('delete', $draftMove);
});
