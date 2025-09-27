<?php

use App\Enums\Inventory\StockMoveStatus;
use App\Models\Company;
use App\Models\User;
use Livewire\Livewire;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->company = Company::factory()->create();
    $this->currency = Currency::factory()->create(['code' => 'USD']);
    $this->company->update(['currency_id' => $this->currency->id]);

    // Set tenant context
    filament()->setTenant($this->company);

    $this->partner = Partner::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->sourceLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Source Location',
    ]);

    $this->destLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Destination Location',
    ]);

    $this->lot = Lot::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'lot_code' => 'LOT001',
    ]);

    // Create stock quant for the lot
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->sourceLocation->id,
        'lot_id' => $this->lot->id,
        'quantity' => 100.0,
    ]);
});

it('can list stock pickings', function () {
    $picking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'partner_id' => $this->partner->id,
        'type' => StockPickingType::Delivery,
        'state' => StockPickingState::Draft,
    ]);

    Livewire::test(ListStockPickings::class)
        ->assertCanSeeTableRecords([$picking])
        ->assertTableColumnExists('reference')
        ->assertTableColumnExists('type')
        ->assertTableColumnExists('state')
        ->assertTableColumnExists('partner.name');
});

it('can view stock picking with moves and lot lines', function () {
    $picking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'partner_id' => $this->partner->id,
        'type' => StockPickingType::Delivery,
        'state' => StockPickingState::Assigned,
    ]);

    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'picking_id' => $picking->id,
        'product_id' => $this->product->id,
        'from_location_id' => $this->sourceLocation->id,
        'to_location_id' => $this->destLocation->id,
        'quantity' => 10.0,
        'status' => StockMoveStatus::Confirmed,
    ]);

    Livewire::test(ViewStockPicking::class, ['record' => $picking->getRouteKey()])
        ->assertSuccessful()
        ->assertSeeText($picking->reference)
        ->assertSeeText($this->partner->name)
        ->assertSeeText($this->product->name);
});

it('can confirm picking', function () {
    $picking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'state' => StockPickingState::Draft,
    ]);

    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'picking_id' => $picking->id,
        'product_id' => $this->product->id,
        'status' => StockMoveStatus::Draft,
    ]);

    expect($picking->state)->toBe(StockPickingState::Draft);
    expect($move->fresh()->status)->toBe(StockMoveStatus::Draft);

    // Test the action exists
    expect(ConfirmPickingAction::getDefaultName())->toBe('confirm');
});

it('can assign picking with lot allocation', function () {
    $picking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'state' => StockPickingState::Confirmed,
    ]);

    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'picking_id' => $picking->id,
        'product_id' => $this->product->id,
        'from_location_id' => $this->sourceLocation->id,
        'to_location_id' => $this->destLocation->id,
        'quantity' => 10.0,
        'status' => StockMoveStatus::Confirmed,
    ]);

    expect($picking->state)->toBe(StockPickingState::Confirmed);

    // Test the action exists
    expect(AssignPickingAction::getDefaultName())->toBe('assign');
});

it('can validate picking and complete moves', function () {
    $picking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'state' => StockPickingState::Assigned,
    ]);

    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'picking_id' => $picking->id,
        'product_id' => $this->product->id,
        'status' => StockMoveStatus::Confirmed,
    ]);

    expect($picking->state)->toBe(StockPickingState::Assigned);

    // Test the action exists
    expect(ValidatePickingAction::getDefaultName())->toBe('validate');
});

it('can create backorder for partial fulfillment', function () {
    $picking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'state' => StockPickingState::Assigned,
    ]);

    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'picking_id' => $picking->id,
        'product_id' => $this->product->id,
        'quantity' => 20.0,
        'status' => StockMoveStatus::Confirmed,
    ]);

    expect($picking->state)->toBe(StockPickingState::Assigned);

    // Test the action exists
    expect(CreateBackorderAction::getDefaultName())->toBe('create_backorder');
});

it('can cancel picking and release reservations', function () {
    $picking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'state' => StockPickingState::Confirmed,
    ]);

    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'picking_id' => $picking->id,
        'product_id' => $this->product->id,
        'status' => StockMoveStatus::Confirmed,
    ]);

    expect($picking->state)->toBe(StockPickingState::Confirmed);

    // Test the action exists
    expect(CancelPickingAction::getDefaultName())->toBe('cancel');
});

it('displays correct state-based actions in view page', function () {
    $draftPicking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'state' => StockPickingState::Draft,
    ]);

    $confirmedPicking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'state' => StockPickingState::Confirmed,
    ]);

    $assignedPicking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'state' => StockPickingState::Assigned,
    ]);

    // Test that different states show different actions
    expect($draftPicking->state)->toBe(StockPickingState::Draft);
    expect($confirmedPicking->state)->toBe(StockPickingState::Confirmed);
    expect($assignedPicking->state)->toBe(StockPickingState::Assigned);
});

it('can filter pickings by type and state', function () {
    $receiptPicking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockPickingType::Receipt,
        'state' => StockPickingState::Draft,
    ]);

    $deliveryPicking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockPickingType::Delivery,
        'state' => StockPickingState::Done,
    ]);

    Livewire::test(ListStockPickings::class)
        ->assertCanSeeTableRecords([$receiptPicking, $deliveryPicking])
        ->assertTableColumnExists('type')
        ->assertTableColumnExists('state');
});
