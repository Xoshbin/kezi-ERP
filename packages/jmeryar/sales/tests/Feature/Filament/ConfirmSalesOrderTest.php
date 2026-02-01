<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Inventory\Enums\Inventory\InventoryAccountingMode;
use Jmeryar\Product\Models\Product;
use Jmeryar\Sales\Enums\Sales\SalesOrderStatus;
use Jmeryar\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\EditSalesOrder;
use Jmeryar\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\ViewSalesOrder;
use Jmeryar\Sales\Models\SalesOrder;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);

    // Set company to manual mode so delivery auto-confirmation doesn't change the status
    $this->company->update([
        'inventory_accounting_mode' => InventoryAccountingMode::MANUAL_INVENTORY_RECORDING,
    ]);
});

it('has confirm action on view page', function () {
    /** @var Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var SalesOrder $salesOrder */
    $salesOrder = SalesOrder::create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'so_date' => now(),
        'so_number' => 'SO-TEST-001',
        'status' => SalesOrderStatus::Draft,
        'total_amount' => Money::of(0, $this->company->currency->code),
        'created_by_user_id' => $this->user->id,
    ]);

    /** @var Product $product */
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Product\Enums\Products\ProductType::Storable,
    ]);

    $salesOrder->lines()->create([
        'product_id' => $product->id,
        'quantity' => 10,
        'unit_price' => Money::of(100, $this->company->currency->code),
        'subtotal' => Money::of(1000, $this->company->currency->code),
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'total' => Money::of(1000, $this->company->currency->code),
        'company_id' => $this->company->id,
        'description' => 'Test Product Line',
    ]);

    livewire(ViewSalesOrder::class, [
        'record' => $salesOrder->getRouteKey(),
    ])
        ->assertActionExists('confirm')
        ->callAction('confirm')
        ->assertNotified();

    expect($salesOrder->refresh()->status)->toBe(SalesOrderStatus::Confirmed);
});

it('confirms sales order from edit page without redirecting', function () {
    /** @var Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var SalesOrder $salesOrder */
    $salesOrder = SalesOrder::create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'so_date' => now(),
        'so_number' => 'SO-TEST-002',
        'status' => SalesOrderStatus::Draft,
        'total_amount' => Money::of(0, $this->company->currency->code),
        'created_by_user_id' => $this->user->id,
    ]);

    /** @var Product $product */
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Product\Enums\Products\ProductType::Storable,
    ]);

    $salesOrder->lines()->create([
        'product_id' => $product->id,
        'quantity' => 10,
        'unit_price' => Money::of(100, $this->company->currency->code),
        'subtotal' => Money::of(1000, $this->company->currency->code),
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'total' => Money::of(1000, $this->company->currency->code),
        'company_id' => $this->company->id,
        'description' => 'Test Product Line',
    ]);

    livewire(EditSalesOrder::class, [
        'record' => $salesOrder->getRouteKey(),
    ])
        ->assertActionExists('confirm')
        ->callAction('confirm')
        ->assertNotified()
        ->assertStatus(200);

    expect($salesOrder->refresh()->status)->toBe(SalesOrderStatus::Confirmed);
});

it('assigns so_number on confirmation', function () {
    /** @var Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var SalesOrder $salesOrder */
    $salesOrder = SalesOrder::create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'so_date' => now(),
        'so_number' => null, // Explicitly null before confirmation
        'status' => SalesOrderStatus::Draft,
        'total_amount' => Money::of(0, $this->company->currency->code),
        'created_by_user_id' => $this->user->id,
    ]);

    /** @var Product $product */
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Product\Enums\Products\ProductType::Storable,
    ]);

    $salesOrder->lines()->create([
        'product_id' => $product->id,
        'quantity' => 5,
        'unit_price' => Money::of(500, $this->company->currency->code),
        'subtotal' => Money::of(2500, $this->company->currency->code),
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'total' => Money::of(2500, $this->company->currency->code),
        'company_id' => $this->company->id,
        'description' => 'Test Product',
    ]);

    expect($salesOrder->so_number)->toBeNull();

    livewire(ViewSalesOrder::class, [
        'record' => $salesOrder->getRouteKey(),
    ])
        ->callAction('confirm')
        ->assertNotified();

    $salesOrder->refresh();

    expect($salesOrder->status)->toBe(SalesOrderStatus::Confirmed);
    expect($salesOrder->so_number)->not->toBeNull();
    expect($salesOrder->so_number)->toStartWith('SO-');
});

it('sets confirmed_at timestamp on confirmation', function () {
    /** @var Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var SalesOrder $salesOrder */
    $salesOrder = SalesOrder::create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'so_date' => now(),
        'status' => SalesOrderStatus::Draft,
        'total_amount' => Money::of(0, $this->company->currency->code),
        'created_by_user_id' => $this->user->id,
    ]);

    /** @var Product $product */
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Product\Enums\Products\ProductType::Storable,
    ]);

    $salesOrder->lines()->create([
        'product_id' => $product->id,
        'quantity' => 5,
        'unit_price' => Money::of(500, $this->company->currency->code),
        'subtotal' => Money::of(2500, $this->company->currency->code),
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'total' => Money::of(2500, $this->company->currency->code),
        'company_id' => $this->company->id,
        'description' => 'Test Product',
    ]);

    expect($salesOrder->confirmed_at)->toBeNull();

    livewire(ViewSalesOrder::class, [
        'record' => $salesOrder->getRouteKey(),
    ])
        ->callAction('confirm')
        ->assertNotified();

    $salesOrder->refresh();

    expect($salesOrder->status)->toBe(SalesOrderStatus::Confirmed);
    expect($salesOrder->confirmed_at)->not->toBeNull();
    expect($salesOrder->confirmed_at)->toBeInstanceOf(\Carbon\Carbon::class);
});
