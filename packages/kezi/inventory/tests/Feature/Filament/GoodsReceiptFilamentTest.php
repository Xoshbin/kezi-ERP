<?php

namespace Kezi\Inventory\tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Models\Partner;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Enums\Inventory\StockPickingType;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\ListStockPickings;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\ViewStockPicking;
use Kezi\Inventory\Models\StockPicking;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\PurchaseOrderLine;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);

    $this->storableProduct = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'name' => 'Test Storable Product',
    ]);

    Filament::setTenant($this->company);
});

describe('GRN Creation on PO Confirmation', function () {
    test('confirming a PO with storable products creates draft GRN in background', function () {
        // Arrange: Create draft PO with storable product
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'created_by_user_id' => $this->user->id,
            'status' => PurchaseOrderStatus::Draft,
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $this->storableProduct->id,
            'quantity' => 10,
        ]);

        // Act: Confirm the PO through Filament action
        Livewire::test(EditPurchaseOrder::class, [
            'record' => $purchaseOrder->getRouteKey(),
            'tenant' => $this->company,
        ])
            ->callAction('confirm')
            ->assertHasNoActionErrors();

        // Assert: A draft GRN was created
        $purchaseOrder->refresh();
        expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::ToReceive);

        $grn = StockPicking::where('purchase_order_id', $purchaseOrder->id)->first();
        expect($grn)->not->toBeNull()
            ->and($grn->type)->toBe(StockPickingType::Receipt)
            ->and($grn->state)->toBe(StockPickingState::Draft)
            ->and($grn->partner_id)->toBe($this->vendor->id);
    });

    test('confirming a PO with consumable products does not create GRN', function () {
        // Arrange: Create consumable product
        $consumableProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => ProductType::Consumable,
            'name' => 'Test Consumable Product',
        ]);

        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'created_by_user_id' => $this->user->id,
            'status' => PurchaseOrderStatus::Draft,
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $consumableProduct->id,
            'quantity' => 10,
        ]);

        // Act: Confirm the PO
        Livewire::test(EditPurchaseOrder::class, [
            'record' => $purchaseOrder->getRouteKey(),
            'tenant' => $this->company,
        ])
            ->callAction('confirm')
            ->assertHasNoActionErrors();

        // Assert: No GRN was created (consumables don't trigger GRN)
        $grn = StockPicking::where('purchase_order_id', $purchaseOrder->id)->first();
        expect($grn)->toBeNull();
    });
});

describe('StockPicking Resource with GRN', function () {
    test('can render stock picking list page', function () {
        $this->get(StockPickingResource::getUrl('index', tenant: $this->company))
            ->assertSuccessful();
    });

    test('GRN is visible in stock picking list', function () {
        // Arrange: Create a GRN
        $grn = StockPicking::factory()->create([
            'company_id' => $this->company->id,
            'partner_id' => $this->vendor->id,
            'type' => StockPickingType::Receipt,
            'state' => StockPickingState::Draft,
            'reference' => 'GRN-TEST-001',
        ]);

        // Act & Assert
        Livewire::test(ListStockPickings::class, ['tenant' => $this->company])
            ->assertCanSeeTableRecords([$grn]);
    });

    test('can view stock picking with PO link', function () {
        // Arrange: Create PO and linked GRN
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'created_by_user_id' => $this->user->id,
            'status' => PurchaseOrderStatus::ToReceive,
        ]);

        $grn = StockPicking::factory()->create([
            'company_id' => $this->company->id,
            'partner_id' => $this->vendor->id,
            'purchase_order_id' => $purchaseOrder->id,
            'type' => StockPickingType::Receipt,
            'state' => StockPickingState::Draft,
        ]);

        // Act & Assert
        $this->get(StockPickingResource::getUrl('view', ['record' => $grn], tenant: $this->company))
            ->assertSuccessful();
    });

    test('GRN shows PO information in view page', function () {
        // Arrange: Create PO and linked GRN
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'created_by_user_id' => $this->user->id,
            'status' => PurchaseOrderStatus::ToReceive,
            'po_number' => 'PO-2026-001',
        ]);

        $grn = StockPicking::factory()->create([
            'company_id' => $this->company->id,
            'partner_id' => $this->vendor->id,
            'purchase_order_id' => $purchaseOrder->id,
            'type' => StockPickingType::Receipt,
            'state' => StockPickingState::Draft,
            'origin' => 'PO-2026-001',
        ]);

        // Act & Assert: The view page renders and contains PO reference
        Livewire::test(ViewStockPicking::class, [
            'record' => $grn->getRouteKey(),
            'tenant' => $this->company,
        ])
            ->assertSuccessful()
            ->assertSee('PO-2026-001');
    });
});

describe('GRN Filtering and Status', function () {
    test('can filter stock pickings by type (Receipt)', function () {
        // Arrange: Create both receipt and delivery pickings
        $receipt = StockPicking::factory()->create([
            'company_id' => $this->company->id,
            'type' => StockPickingType::Receipt,
            'state' => StockPickingState::Draft,
        ]);

        $delivery = StockPicking::factory()->create([
            'company_id' => $this->company->id,
            'type' => StockPickingType::Delivery,
            'state' => StockPickingState::Draft,
        ]);

        // Act & Assert: Filter by Receipt type
        Livewire::test(ListStockPickings::class, ['tenant' => $this->company])
            ->filterTable('type', StockPickingType::Receipt->value)
            ->assertCanSeeTableRecords([$receipt])
            ->assertCanNotSeeTableRecords([$delivery]);
    });

    test('can filter stock pickings by state (Draft)', function () {
        // Arrange
        $draftPicking = StockPicking::factory()->create([
            'company_id' => $this->company->id,
            'type' => StockPickingType::Receipt,
            'state' => StockPickingState::Draft,
        ]);

        $donePicking = StockPicking::factory()->create([
            'company_id' => $this->company->id,
            'type' => StockPickingType::Receipt,
            'state' => StockPickingState::Done,
        ]);

        // Act & Assert
        Livewire::test(ListStockPickings::class, ['tenant' => $this->company])
            ->filterTable('state', StockPickingState::Draft->value)
            ->assertCanSeeTableRecords([$draftPicking])
            ->assertCanNotSeeTableRecords([$donePicking]);
    });
});

describe('GRN with StockMoves', function () {
    test('GRN created from PO has correct stock moves', function () {
        // Arrange: Create and confirm PO with multiple storable products
        $product1 = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => ProductType::Storable,
        ]);
        $product2 = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => ProductType::Storable,
        ]);

        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'created_by_user_id' => $this->user->id,
            'status' => PurchaseOrderStatus::Draft,
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product1->id,
            'quantity' => 5,
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product2->id,
            'quantity' => 10,
        ]);

        // Act: Confirm the PO through Filament
        Livewire::test(EditPurchaseOrder::class, [
            'record' => $purchaseOrder->getRouteKey(),
            'tenant' => $this->company,
        ])
            ->callAction('confirm')
            ->assertHasNoActionErrors();

        // Assert: GRN has stock moves for both products
        $grn = StockPicking::where('purchase_order_id', $purchaseOrder->id)->first();
        expect($grn)->not->toBeNull();
        expect($grn->stockMoves)->toHaveCount(2);

        // Check product lines through stock moves
        $allProductLines = $grn->stockMoves->flatMap->productLines;
        $productIds = $allProductLines->pluck('product_id')->unique()->values();

        expect($productIds)->toContain($product1->id)
            ->and($productIds)->toContain($product2->id);
    });
});
