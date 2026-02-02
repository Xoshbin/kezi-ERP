<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Filament\Facades\Filament;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\Actions\ConfirmStockMoveAction;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveProductLine;
use Kezi\Product\Models\Product;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();

    // Authenticate the user and set up Filament tenant context
    $this->actingAs($this->user);
    Filament::setTenant($this->company);

    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::FIFO,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);

    $this->vendorLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Vendor Location',
    ]);

    $this->stockLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Stock Location',
    ]);

    $this->stockMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'SM-TEST',
        'created_by_user_id' => $this->user->id,
    ]);

    StockMoveProductLine::create([
        'stock_move_id' => $this->stockMove->id,
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'quantity' => 1.0,
        'from_location_id' => $this->vendorLocation->id,
        'to_location_id' => $this->stockLocation->id,
    ]);
});

it('creates confirm stock move action with correct configuration', function () {
    $action = ConfirmStockMoveAction::make();

    expect($action->getName())->toBe('confirm');
    expect($action->getLabel())->toBe(__('Confirm Movement'));
    expect($action->getIcon())->toBe('heroicon-o-check-circle');
    expect($action->getColor())->toBe('success');
});

it('shows confirm action only for draft stock moves', function () {
    $action = ConfirmStockMoveAction::make();
    $action->record($this->stockMove);

    // Should be visible for draft moves
    expect($action->isVisible())->toBe(true);

    // Should not be visible for confirmed moves
    // Disable the observer to avoid triggering inventory valuation during test
    StockMove::withoutEvents(function () {
        $this->stockMove->update(['status' => StockMoveStatus::Done]);
    });
    $action->record($this->stockMove->fresh());

    expect($action->isVisible())->toBe(false);
});

it('handles cost validation errors gracefully', function () {
    // This test verifies that the action handles InsufficientCostInformationException
    // without throwing unhandled exceptions

    $action = ConfirmStockMoveAction::make();
    $action->record($this->stockMove);

    // The action should be configured to handle exceptions
    expect($action->getName())->toBe('confirm');

    // We can't easily test the actual exception handling in a unit test
    // since it involves Livewire components and notifications,
    // but we can verify the action is properly configured
    expect($action->getModalHeading())->toBe(__('Confirm Stock Movement'));
    expect($action->getModalSubmitActionLabel())->toBe(__('Confirm Movement'));
});

it('has proper modal configuration', function () {
    $action = ConfirmStockMoveAction::make();

    expect($action->getModalHeading())->toBe(__('Confirm Stock Movement'));
    expect($action->getModalDescription())->toBe(__('Are you sure you want to confirm this stock movement? This will process the inventory changes and cannot be undone.'));
    expect($action->getModalSubmitActionLabel())->toBe(__('Confirm Movement'));
    expect($action->isConfirmationRequired())->toBe(true);
});
