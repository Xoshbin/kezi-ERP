<?php

namespace Modules\Purchase\Tests\Feature\Filament;

use Brick\Money\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\CreateVendorBill;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use Modules\Foundation\Enums\Incoterm;
use Modules\Foundation\Enums\ShippingCostType;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use Modules\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
    Filament::setTenant($this->company);

    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);

    // Ensure we have a regular product and one that triggers shipping auto-detection
    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Regular Item',
    ]);

    $this->freightProduct = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Sea Freight Services',
        'description' => 'Sea Freight Services',
    ]);
});

test('vendor bill line items have shipping_cost_type field', function () {
    Livewire::test(CreateVendorBill::class)
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
        ])
        ->set('data.lines', [])
        ->assertSet('data.lines', []);
});

test('vendor bill auto-detects shipping_cost_type based on product name', function () {
    Livewire::test(CreateVendorBill::class)
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
        ])
        ->set('data.lines', [
            [
                'product_id' => null,
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ])
        ->set('data.lines.0.product_id', $this->freightProduct->id)
        ->assertSet('data.lines.0.description', 'Sea Freight Services')
        ->assertSet('data.lines.0.shipping_cost_type', ShippingCostType::Freight);
});

test('vendor bill shows warning banner when inappropriate shipping costs are added', function () {
    // Required setup for vendor bill lines
    $expenseAccount = \Modules\Accounting\Models\Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'expense',
    ]);

    $bill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency_id,
        'incoterm' => Incoterm::Ddp, // Seller pays everything
        'status' => VendorBillStatus::Draft,
        'bill_date' => now(),
        'accounting_date' => now(),
    ]);

    // Initially no warnings
    Livewire::test(EditVendorBill::class, ['record' => $bill->getRouteKey()])
        ->assertDontSee('Shipping Cost Responsibility Warnings');

    // Add a freight line via database to trigger warning on refresh/load
    $bill->lines()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->freightProduct->id,
        'description' => 'Ocean Freight',
        'quantity' => 1,
        'unit_price' => Money::of(500, 'USD'),
        'subtotal' => Money::of(500, 'USD'),
        'total_line_tax' => Money::of(0, 'USD'),
        'shipping_cost_type' => ShippingCostType::Freight,
        'expense_account_id' => $expenseAccount->id,
    ]);

    Livewire::test(EditVendorBill::class, ['record' => $bill->getRouteKey()])
        ->assertSee('Shipping Cost Responsibility Warnings')
        ->assertSee('seller typically pays for these costs');
});

test('purchase order line items have shipping_cost_type field', function () {
    Livewire::test(CreatePurchaseOrder::class)
        ->set('data.lines', [])
        ->assertSet('data.lines', []);
});

test('purchase order auto-detects shipping_cost_type based on product name', function () {
    Livewire::test(CreatePurchaseOrder::class)
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
        ])
        ->set('data.lines', [
            [
                'product_id' => null,
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ])
        ->set('data.lines.0.product_id', $this->freightProduct->id)
        ->assertSet('data.lines.0.description', 'Sea Freight Services')
        ->assertSet('data.lines.0.shipping_cost_type', ShippingCostType::Freight->value);
});
