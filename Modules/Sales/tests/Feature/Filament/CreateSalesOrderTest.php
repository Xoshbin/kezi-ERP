<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Sales\Enums\Sales\SalesOrderStatus;
use Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\CreateSalesOrder;
use Modules\Sales\Models\SalesOrder;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    // Acting as the authenticated user
    $this->actingAs($this->user);
});

it('can create a sales order', function () {
    /** @var Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var Product $product */
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Product Line',
        'unit_price' => Money::of(100, $this->company->currency->code),
    ]);

    livewire(CreateSalesOrder::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'currency_id' => $this->company->currency_id,
            'so_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => now()->addDays(7)->format('Y-m-d'),
        ])
        ->set('data.lines', [
            [
                'product_id' => $product->id,
                'description' => 'Test Product Line',
                'quantity' => 2,
                'unit_price' => $product->unit_price->getMinorAmount()->toInt(),
                'tax_id' => null,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('sales_orders', [
        'company_id' => $this->company->id, // This ensures company_id is correctly set
        'customer_id' => $customer->id,
        'status' => SalesOrderStatus::Draft->value,
    ]);

    $this->assertDatabaseHas('sales_order_lines', [
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $salesOrder = SalesOrder::first();
    expect($salesOrder->total_amount->getAmount()->toFloat())->toBe(200.0);
    expect($salesOrder->created_by_user_id)->toBe($this->user->id); // Verify user is set
});

it('auto populates unit price when product is selected', function () {
    /** @var Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var Product $product */
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Product For Auto Populate',
        'unit_price' => Money::of(150, $this->company->currency->code),
    ]);

    $uuid = (string) \Illuminate\Support\Str::uuid();

    livewire(CreateSalesOrder::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'currency_id' => $this->company->currency_id,
        ])
        // Manually set the lines state with a UUID key to simulate adding a row
        ->set('data.lines', [
            $uuid => [
                'product_id' => null,
                'quantity' => 1,
            ],
        ])
        // Now set the product_id on that specific line to trigger the auto-population logic
        ->set("data.lines.$uuid.product_id", $product->id)
        // Check if unit_price is populated correctly
        ->assertFormSet([
            "lines.$uuid.unit_price" => '150.000', // Expecting formatted string or just 150
        ]);
});
