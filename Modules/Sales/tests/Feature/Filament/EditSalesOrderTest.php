<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Sales\Enums\Sales\SalesOrderStatus;
use Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\EditSalesOrder;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    // Acting as the authenticated user
    $this->actingAs($this->user);
});

it('can update sales order lines through filament edit page', function () {
    // Create products
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $product2 = Product::factory()->create(['company_id' => $this->company->id]);

    /** @var Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    // Create a sales order with a line
    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'created_by_user_id' => $this->user->id,
        'status' => SalesOrderStatus::Draft,
    ]);

    SalesOrderLine::factory()->create([
        'sales_order_id' => $salesOrder->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'unit_price' => Money::of(100, $this->company->currency->code),
        'description' => 'Original product line',
    ]);

    // Edit the sales order - change quantity and add a new line
    $livewire = livewire(EditSalesOrder::class, [
        'record' => $salesOrder->getRouteKey(),
    ]);

    $livewire->set('data.lines', [
        [
            'product_id' => $product->id,
            'description' => 'Updated product line',
            'quantity' => 10, // Changed from 5 to 10
            'unit_price' => '150.00', // Changed from 100 to 150
            'tax_id' => null,
        ],
        [
            'product_id' => $product2->id,
            'description' => 'New second line',
            'quantity' => 3,
            'unit_price' => '50.00',
            'tax_id' => null,
        ],
    ]);

    $livewire->fillForm([
        'reference' => 'UPDATED-REF-001',
    ]);

    $livewire->call('save')
        ->assertHasNoFormErrors();

    // Verify the changes were saved
    $salesOrder->refresh();
    $salesOrder->load('lines');

    expect($salesOrder->reference)->toBe('UPDATED-REF-001');
    expect($salesOrder->lines)->toHaveCount(2);

    // Check the first line was updated
    $firstLine = $salesOrder->lines->where('product_id', $product->id)->first();
    expect($firstLine)->not->toBeNull();
    expect($firstLine->quantity)->toBe(10.0);
    expect($firstLine->description)->toBe('Updated product line');

    // Check the second line was added
    $secondLine = $salesOrder->lines->where('product_id', $product2->id)->first();
    expect($secondLine)->not->toBeNull();
    expect($secondLine->quantity)->toBe(3.0);
    expect($secondLine->description)->toBe('New second line');
});

it('cannot update confirmed sales order', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);

    /** @var Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    // Create a confirmed sales order
    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'created_by_user_id' => $this->user->id,
        'status' => SalesOrderStatus::Confirmed,
    ]);

    SalesOrderLine::factory()->create([
        'sales_order_id' => $salesOrder->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'unit_price' => Money::of(100, $this->company->currency->code),
        'description' => 'Original product line',
    ]);

    // Try to update the confirmed sales order - should show notification and halt
    $livewire = livewire(EditSalesOrder::class, [
        'record' => $salesOrder->getRouteKey(),
    ]);

    $livewire->set('data.lines', [
        [
            'product_id' => $product->id,
            'description' => 'Updated product line',
            'quantity' => 10,
            'unit_price' => '150.00',
            'tax_id' => null,
        ],
    ]);

    // The save action should be halted and a notification shown
    $livewire->call('save')
        ->assertNotified();

    // The order should not have been updated due to status restriction
    $salesOrder->refresh();
    expect($salesOrder->lines->first()->quantity)->toBe(5.0);
});

it('loads existing lines when editing a sales order', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);

    /** @var Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'created_by_user_id' => $this->user->id,
        'status' => SalesOrderStatus::Draft,
    ]);

    SalesOrderLine::factory()->create([
        'sales_order_id' => $salesOrder->id,
        'product_id' => $product->id,
        'quantity' => 7,
        'unit_price' => Money::of(250, $this->company->currency->code),
        'description' => 'Existing line item',
    ]);

    $livewire = livewire(EditSalesOrder::class, [
        'record' => $salesOrder->getRouteKey(),
    ]);

    // Check that the form was populated with the existing line data
    $livewire->assertFormSet([
        'lines.0.product_id' => $product->id,
        'lines.0.description' => 'Existing line item',
        'lines.0.quantity' => 7.0,
    ]);
});
