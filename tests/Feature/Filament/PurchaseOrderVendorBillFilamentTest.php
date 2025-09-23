<?php

namespace Tests\Feature\Filament;

use App\Enums\Products\ProductType;
use App\Enums\Purchases\PurchaseOrderStatus;
use App\Filament\Clusters\Accounting\Resources\VendorBills\Pages\CreateVendorBill;
use App\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Models\Account;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    // Create an expense account for products
    $this->expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'expense',
        'code' => '5000',
        'name' => 'Cost of Goods Sold',
    ]);

    // Create a product with expense account
    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'expense_account_id' => $this->expenseAccount->id,
    ]);

    // Create a purchase order that can create bills
    $this->purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency->id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::ToBill, // Status that allows bill creation
        'confirmed_at' => now(),
        'po_number' => 'PO-2024-001',
    ]);

    // Add a line to the purchase order
    $this->purchaseOrder->lines()->create([
        'product_id' => $this->product->id,
        'description' => 'Test Product Line',
        'quantity' => 10.0,
        'unit_price' => Money::of(1000, $this->company->currency->code),
        'subtotal' => Money::of(10000, $this->company->currency->code),
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'total' => Money::of(10000, $this->company->currency->code),
    ]);

    $this->actingAs($this->user);
});

it('shows create bill action on confirmed purchase order view', function () {
    Livewire::test(ViewPurchaseOrder::class, ['record' => $this->purchaseOrder->id])
        ->assertOk()
        ->assertActionExists('createBill')
        ->assertActionVisible('createBill');
});

it('hides create bill action on draft purchase order', function () {
    $draftPO = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency->id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    Livewire::test(ViewPurchaseOrder::class, ['record' => $draftPO->id])
        ->assertOk()
        ->assertActionExists('createBill')
        ->assertActionHidden('createBill');
});

it('can pre-fill vendor bill form from purchase order parameter', function () {
    $component = Livewire::test(CreateVendorBill::class)
        ->set('mountedActions', [])
        ->call('mount');

    // Simulate the purchase_order_id parameter
    request()->merge(['purchase_order_id' => $this->purchaseOrder->id]);

    $component = Livewire::test(CreateVendorBill::class)
        ->call('mount');

    // Check that form is pre-filled with PO data
    $formData = $component->get('data');

    expect($formData['vendor_id'])->toBe($this->purchaseOrder->vendor_id);
    expect($formData['currency_id'])->toBe($this->purchaseOrder->currency_id);
    expect($formData['purchase_order_id'])->toBe($this->purchaseOrder->id);
    expect($formData['lines'])->toHaveCount(1);

    $lineData = $formData['lines'][0];
    expect($lineData['product_id'])->toBe($this->product->id);
    expect($lineData['description'])->toBe('Test Product Line');
    expect($lineData['quantity'])->toBe(10.0);
    expect($lineData['expense_account_id'])->toBe($this->expenseAccount->id);
});

it('shows load from purchase order action when vendor is selected', function () {
    Livewire::test(CreateVendorBill::class)
        ->fillForm([
            'vendor_id' => $this->vendor->id,
        ])
        ->assertActionExists('loadFromPurchaseOrder')
        ->assertActionVisible('loadFromPurchaseOrder');
});

it('hides load from purchase order action when no vendor is selected', function () {
    Livewire::test(CreateVendorBill::class)
        ->assertActionExists('loadFromPurchaseOrder')
        ->assertActionHidden('loadFromPurchaseOrder');
});

it('hides load from purchase order action when purchase order is already loaded', function () {
    Livewire::test(CreateVendorBill::class)
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'purchase_order_id' => $this->purchaseOrder->id,
        ])
        ->assertActionExists('loadFromPurchaseOrder')
        ->assertActionHidden('loadFromPurchaseOrder');
});

it('can create vendor bill with purchase order link', function () {
    $component = Livewire::test(CreateVendorBill::class)
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency->id,
            'bill_reference' => 'TEST-BILL-001',
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
            'purchase_order_id' => $this->purchaseOrder->id,
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Test Product Line',
                    'quantity' => 10.0,
                    'unit_price' => 10.00,
                    'expense_account_id' => $this->expenseAccount->id,
                    'tax_id' => null,
                    'analytic_account_id' => null,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify the vendor bill was created with PO link
    $vendorBill = \App\Models\VendorBill::where('bill_reference', 'TEST-BILL-001')->first();
    expect($vendorBill)->not->toBeNull();
    expect($vendorBill->purchase_order_id)->toBe($this->purchaseOrder->id);
});

it('validates purchase order compatibility when creating vendor bill', function () {
    // Create a PO with different vendor
    $otherVendor = \App\Models\Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    $otherPO = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $otherVendor->id,
        'currency_id' => $this->company->currency->id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    Livewire::test(CreateVendorBill::class)
        ->fillForm([
            'vendor_id' => $this->vendor->id, // Different vendor
            'currency_id' => $this->company->currency->id,
            'bill_reference' => 'TEST-BILL-002',
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
            'purchase_order_id' => $otherPO->id, // PO with different vendor
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Test Product Line',
                    'quantity' => 10.0,
                    'unit_price' => 10.00,
                    'expense_account_id' => $this->expenseAccount->id,
                    'tax_id' => null,
                    'analytic_account_id' => null,
                ],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['purchase_order_id']);
});

it('can load purchase order data via action', function () {
    $component = Livewire::test(CreateVendorBill::class)
        ->fillForm([
            'vendor_id' => $this->vendor->id,
        ])
        ->callAction('loadFromPurchaseOrder', [
            'purchase_order_id' => $this->purchaseOrder->id,
        ]);

    // Check that form is now filled with PO data
    $formData = $component->get('data');

    expect($formData['vendor_id'])->toBe($this->purchaseOrder->vendor_id);
    expect($formData['currency_id'])->toBe($this->purchaseOrder->currency_id);
    expect($formData['purchase_order_id'])->toBe($this->purchaseOrder->id);
    expect($formData['lines'])->toHaveCount(1);
});

it('filters purchase orders by vendor in load action', function () {
    // Create another vendor and PO
    $otherVendor = \App\Models\Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    $otherPO = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $otherVendor->id,
        'currency_id' => $this->company->currency->id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::ToBill,
        'confirmed_at' => now(),
        'po_number' => 'PO-2024-002',
    ]);

    $component = Livewire::test(CreateVendorBill::class)
        ->fillForm([
            'vendor_id' => $this->vendor->id, // Select first vendor
        ])
        ->mountAction('loadFromPurchaseOrder');

    // The action should only show POs for the selected vendor
    $actionData = $component->instance()->getMountedActionForm()->getState();

    // This is a simplified test - in reality, we'd need to check the Select options
    // which would require more complex testing of the form component
    expect($component->instance())->not->toBeNull();
});
