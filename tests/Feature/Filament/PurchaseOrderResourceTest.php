<?php

use App\Enums\Purchases\PurchaseOrderStatus;
use App\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Clusters\Purchases\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\Partner;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);

    \Filament\Facades\Filament::setTenant($this->company);
});

test('can render purchase order list page', function () {
    $this->get(PurchaseOrderResource::getUrl('index', tenant: $this->company))
        ->assertSuccessful();
});

test('can render purchase order create page', function () {
    $this->get(PurchaseOrderResource::getUrl('create', tenant: $this->company))
        ->assertSuccessful();
});

test('can create purchase order through filament', function () {
    $newData = [
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency_id,
        'po_date' => now()->format('Y-m-d'),
        'reference' => 'TEST-REF-001',
        'notes' => 'Test purchase order notes',
    ];

    Livewire::test(CreatePurchaseOrder::class, ['tenant' => $this->company])
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('purchase_orders', [
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency_id,
        'reference' => 'TEST-REF-001',
        'status' => PurchaseOrderStatus::Draft,
    ]);
});

test('can render purchase order edit page', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency_id,
        'created_by_user_id' => $this->user->id,
    ]);

    $this->get(PurchaseOrderResource::getUrl('edit', ['record' => $purchaseOrder], tenant: $this->company))
        ->assertSuccessful();
});

test('can render purchase order view page', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency_id,
        'created_by_user_id' => $this->user->id,
    ]);

    $this->get(PurchaseOrderResource::getUrl('view', ['record' => $purchaseOrder], tenant: $this->company))
        ->assertSuccessful();
});

test('can confirm purchase order through filament action', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency_id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    // Add a line to the purchase order so it can be confirmed
    PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => Product::factory()->create(['company_id' => $this->company->id])->id,
    ]);

    Livewire::test(EditPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey(), 'tenant' => $this->company])
        ->callAction('confirm')
        ->assertHasNoActionErrors();

    $purchaseOrder->refresh();
    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::ToReceive);
    expect($purchaseOrder->confirmed_at)->not->toBeNull();
});

test('can cancel purchase order through filament action', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency_id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    Livewire::test(EditPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey(), 'tenant' => $this->company])
        ->callAction('cancel')
        ->assertHasNoActionErrors();

    $purchaseOrder->refresh();
    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Cancelled);
    expect($purchaseOrder->cancelled_at)->not->toBeNull();
});
