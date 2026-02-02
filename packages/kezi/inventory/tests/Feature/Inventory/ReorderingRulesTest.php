<?php

namespace Kezi\Inventory\Tests\Feature\Inventory;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Inventory\Console\Commands\RunReorderingSchedulerCommand;
use Kezi\Inventory\Enums\Inventory\ReorderingRoute;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Inventory\Models\ReorderingRule;
use Kezi\Inventory\Models\ReplenishmentSuggestion;
use Kezi\Inventory\Models\StockQuant;
use Kezi\Inventory\Services\Inventory\ReorderingRuleService;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    $this->product = Product::factory()->for($this->company)->create([
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'average_cost' => Money::of(100, $this->company->currency->code),
    ]);

    $this->reorderingService = app(ReorderingRuleService::class);
});

it('creates replenishment suggestions when stock falls below minimum', function () {
    // Create reordering rule
    $rule = ReorderingRule::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'min_qty' => 10.0,
        'max_qty' => 50.0,
        'safety_stock' => 5.0,
        'multiple' => 1.0,
        'route' => ReorderingRoute::MinMax,
        'lead_time_days' => 7,
        'active' => true,
    ]);

    // Create current stock below minimum
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'quantity' => 8.0, // Below min_qty of 10
        'reserved_quantity' => 2.0, // Available = 6, below safety stock
    ]);

    // Run scheduler
    $this->reorderingService->generateReplenishmentSuggestions();

    // Assert suggestion was created
    $suggestion = ReplenishmentSuggestion::where('company_id', $this->company->id)
        ->where('product_id', $this->product->id)
        ->where('location_id', $this->stockLocation->id)
        ->first();

    expect($suggestion)->not->toBeNull();
    expect($suggestion->reordering_rule_id)->toBe($rule->id);
    expect($suggestion->suggested_qty)->toBe(42.0); // max_qty - current_qty = 50 - 8
    expect($suggestion->reason)->toContain('Below minimum quantity');
    expect($suggestion->priority)->toBe('normal');
});

it('creates high priority suggestions when stock falls below safety stock', function () {
    $rule = ReorderingRule::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'min_qty' => 10.0,
        'max_qty' => 50.0,
        'safety_stock' => 5.0,
        'multiple' => 1.0,
        'route' => ReorderingRoute::MinMax,
        'lead_time_days' => 7,
        'active' => true,
    ]);

    // Create stock below safety level
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'quantity' => 6.0,
        'reserved_quantity' => 2.0, // Available = 4, below safety stock of 5
    ]);

    $this->reorderingService->generateReplenishmentSuggestions();

    $suggestion = ReplenishmentSuggestion::where('company_id', $this->company->id)
        ->where('product_id', $this->product->id)
        ->first();

    expect($suggestion)->not->toBeNull();
    expect($suggestion->priority)->toBe('high');
    expect($suggestion->reason)->toContain('Below safety stock');
});

it('respects multiple quantity when calculating suggestions', function () {
    $rule = ReorderingRule::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'min_qty' => 10.0,
        'max_qty' => 50.0,
        'safety_stock' => 5.0,
        'multiple' => 12.0, // Must order in multiples of 12
        'route' => ReorderingRoute::MinMax,
        'lead_time_days' => 7,
        'active' => true,
    ]);

    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'quantity' => 8.0,
        'reserved_quantity' => 0.0,
    ]);

    $this->reorderingService->generateReplenishmentSuggestions();

    $suggestion = ReplenishmentSuggestion::first();

    // Need 42 units (50-8), rounded up to next multiple of 12 = 48
    expect($suggestion->suggested_qty)->toBe(48.0);
});

it('creates immediate MTO suggestions for make-to-order products', function () {
    $rule = ReorderingRule::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'min_qty' => 0.0,
        'max_qty' => 0.0,
        'safety_stock' => 0.0,
        'multiple' => 1.0,
        'route' => ReorderingRoute::MTO,
        'lead_time_days' => 14,
        'active' => true,
    ]);

    // Simulate demand (reserved quantity without stock)
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'quantity' => 0.0,
        'reserved_quantity' => 0.0, // No current reservations
    ]);

    // Manually trigger MTO for specific demand
    $demandQty = 25.0;
    $this->reorderingService->createMTOSuggestion($rule, $demandQty, 'Sales Order SO001');

    $suggestion = ReplenishmentSuggestion::where('route', ReorderingRoute::MTO)->first();

    expect($suggestion)->not->toBeNull();
    expect($suggestion->suggested_qty)->toBe($demandQty);
    expect($suggestion->priority)->toBe('urgent');
    expect($suggestion->reason)->toContain('Make-to-Order');
    expect($suggestion->origin_reference)->toBe('Sales Order SO001');
});

it('does not create duplicate suggestions for same rule', function () {
    $rule = ReorderingRule::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'min_qty' => 10.0,
        'max_qty' => 50.0,
        'safety_stock' => 5.0,
        'multiple' => 1.0,
        'route' => ReorderingRoute::MinMax,
        'lead_time_days' => 7,
        'active' => true,
    ]);

    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'quantity' => 8.0,
        'reserved_quantity' => 0.0,
    ]);

    // Run scheduler twice
    $this->reorderingService->generateReplenishmentSuggestions();
    $this->reorderingService->generateReplenishmentSuggestions();

    // Should only have one suggestion
    $suggestions = ReplenishmentSuggestion::where('reordering_rule_id', $rule->id)->get();
    expect($suggestions->count())->toBe(1);
});

it('skips inactive rules', function () {
    $rule = ReorderingRule::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'min_qty' => 10.0,
        'max_qty' => 50.0,
        'safety_stock' => 5.0,
        'multiple' => 1.0,
        'route' => ReorderingRoute::MinMax,
        'lead_time_days' => 7,
        'active' => false, // Inactive rule
    ]);

    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'quantity' => 5.0, // Well below minimum
        'reserved_quantity' => 0.0,
    ]);

    $this->reorderingService->generateReplenishmentSuggestions();

    $suggestions = ReplenishmentSuggestion::where('reordering_rule_id', $rule->id)->get();
    expect($suggestions->count())->toBe(0);
});

it('calculates correct lead time dates', function () {
    $currentDate = Carbon::create(2025, 2, 15);
    Carbon::setTestNow($currentDate);

    $rule = ReorderingRule::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'min_qty' => 10.0,
        'max_qty' => 50.0,
        'safety_stock' => 5.0,
        'multiple' => 1.0,
        'route' => ReorderingRoute::MinMax,
        'lead_time_days' => 10,
        'active' => true,
    ]);

    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'quantity' => 8.0,
        'reserved_quantity' => 0.0,
    ]);

    $this->reorderingService->generateReplenishmentSuggestions();

    $suggestion = ReplenishmentSuggestion::first();

    expect($suggestion->suggested_order_date->format('Y-m-d'))->toBe($currentDate->format('Y-m-d'));
    expect($suggestion->expected_delivery_date->format('Y-m-d'))->toBe($currentDate->addDays(10)->format('Y-m-d'));
});

it('runs scheduler command successfully', function () {
    $rule = ReorderingRule::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'min_qty' => 10.0,
        'max_qty' => 50.0,
        'safety_stock' => 5.0,
        'multiple' => 1.0,
        'route' => ReorderingRoute::MinMax,
        'lead_time_days' => 7,
        'active' => true,
    ]);

    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'quantity' => 8.0,
        'reserved_quantity' => 0.0,
    ]);

    // Run the artisan command
    $this->artisan(RunReorderingSchedulerCommand::class)
        ->expectsOutput('Reordering scheduler completed successfully.')
        ->assertExitCode(0);

    // Verify suggestion was created
    $suggestions = ReplenishmentSuggestion::all();
    expect($suggestions->count())->toBeGreaterThan(0);
});
