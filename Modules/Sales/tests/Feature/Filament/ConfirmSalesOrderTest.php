<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Sales\Enums\Sales\SalesOrderStatus;
use Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\EditSalesOrder;
use Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\ViewSalesOrder;
use Modules\Sales\Models\SalesOrder;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
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
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
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
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
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
