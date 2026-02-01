<?php

namespace Jmeryar\Inventory\Tests\Feature\Inventory;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Inventory\Enums\Inventory\InventoryAccountingMode;
use Jmeryar\Inventory\Enums\Inventory\StockMoveStatus;
use Jmeryar\Inventory\Enums\Inventory\ValuationMethod;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Inventory\Models\StockPicking;
use Jmeryar\Product\Models\Product;
use Jmeryar\Purchase\Actions\Purchases\CreateVendorBillLineAction;
use Jmeryar\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Jmeryar\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Jmeryar\Purchase\Enums\Purchases\VendorBillStatus;
use Jmeryar\Purchase\Models\PurchaseOrder;
use Jmeryar\Purchase\Models\PurchaseOrderLine;
use Jmeryar\Purchase\Models\VendorBill;
use Jmeryar\Purchase\Services\PurchaseOrderService;
use Jmeryar\Purchase\Services\VendorBillService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $this->vendor = Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Product',
        'type' => \Jmeryar\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
    ]);
});

it('creates draft GRN on PO confirmation in AUTO_RECORD_ON_BILL mode but no inventory updates until bill', function () {
    // Arrange: Set company to auto-record mode
    $this->company->update([
        'inventory_accounting_mode' => InventoryAccountingMode::AUTO_RECORD_ON_BILL,
    ]);

    // Create and confirm a Purchase Order
    $po = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'status' => PurchaseOrderStatus::Draft,
        'currency_id' => $this->company->currency_id,
        'po_date' => now()->format('Y-m-d'),
        'expected_delivery_date' => now()->addDays(5)->format('Y-m-d'),
    ]);

    PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'unit_price' => 100 * 10000, // Minor units
    ]);

    // Act: Confirm the PO
    app(PurchaseOrderService::class)->confirm($po, $this->user);

    // Assert: A draft GRN (StockPicking) SHOULD be created for tracking
    $poPicking = StockPicking::where('purchase_order_id', $po->id)->first();
    expect($poPicking)->not->toBeNull();
    expect($poPicking->state)->toBe(\Jmeryar\Inventory\Enums\Inventory\StockPickingState::Draft);

    // Assert: Stock moves should be in Draft state (not Done - no inventory update yet)
    $poMoves = StockMove::whereHas('productLines', function ($q) {
        $q->where('source_type', PurchaseOrderLine::class);
    })->where('status', StockMoveStatus::Done)->count();
    expect($poMoves)->toBe(0);
});

it('creates PO-based stock moves for warehouse tracking in MANUAL mode', function () {
    // Arrange: Set company to manual recording mode
    $this->company->update([
        'inventory_accounting_mode' => InventoryAccountingMode::MANUAL_INVENTORY_RECORDING,
    ]);

    // Create and confirm a Purchase Order
    $po = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'status' => PurchaseOrderStatus::Draft,
        'currency_id' => $this->company->currency_id,
        'po_date' => now()->format('Y-m-d'),
        'expected_delivery_date' => now()->addDays(5)->format('Y-m-d'),
    ]);

    PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'unit_price' => 100 * 10000, // Minor units
    ]);

    // Act: Confirm the PO
    app(PurchaseOrderService::class)->confirm($po, $this->user);

    // Assert: Stock moves SHOULD be created from PO in MANUAL mode
    $poMoves = StockMove::where('source_type', PurchaseOrderLine::class)->get();
    expect($poMoves)->toHaveCount(1);
    expect($poMoves->first()->status)->toBe(StockMoveStatus::Draft);

    // Assert: Stock picking should be created
    $poPicking = StockPicking::where('origin', $po->po_number)->first();
    expect($poPicking)->not->toBeNull();
});

it('does NOT create duplicate stock moves when PO confirmed and Bill posted in AUTO mode', function () {
    // Arrange: Set company to auto-record mode
    $this->company->update([
        'inventory_accounting_mode' => InventoryAccountingMode::AUTO_RECORD_ON_BILL,
    ]);

    // Create and confirm a Purchase Order
    $po = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'status' => PurchaseOrderStatus::Draft,
        'currency_id' => $this->company->currency_id,
        'po_date' => now()->format('Y-m-d'),
        'expected_delivery_date' => now()->addDays(5)->format('Y-m-d'),
    ]);

    PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'unit_price' => 100 * 10000, // Minor units
    ]);

    // Confirm the PO
    app(PurchaseOrderService::class)->confirm($po, $this->user);

    // Verify draft GRN was created
    $poPicking = StockPicking::where('purchase_order_id', $po->id)->first();
    expect($poPicking)->not->toBeNull();

    // Count stock moves BEFORE bill posting (should be in draft state)
    $draftMoves = StockMove::where('status', StockMoveStatus::Draft)->count();

    // Create and post Vendor Bill from PO
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $this->vendor->id,
        'purchase_order_id' => $po->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now()->format('Y-m-d'),
        'accounting_date' => now()->format('Y-m-d'),
        'currency_id' => $this->company->currency_id,
    ]);

    $lineDto = new CreateVendorBillLineDTO(
        product_id: $this->product->id,
        description: 'Test Product Line',
        quantity: 10,
        unit_price: Money::of(100, $this->company->currency->code),
        expense_account_id: $this->product->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);

    // Act: Post the vendor bill
    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Assert: Bill-based stock moves should exist (from VendorBillObserver)
    $billMoves = StockMove::where('source_type', VendorBill::class)->count();
    expect($billMoves)->toBe(1);
});

it('does NOT create Bill-based stock moves in MANUAL mode', function () {
    // Arrange: Set company to manual recording mode
    $this->company->update([
        'inventory_accounting_mode' => InventoryAccountingMode::MANUAL_INVENTORY_RECORDING,
    ]);

    // Create vendor bill with storable product
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now()->format('Y-m-d'),
        'accounting_date' => now()->format('Y-m-d'),
        'currency_id' => $this->company->currency_id,
    ]);

    $lineDto = new CreateVendorBillLineDTO(
        product_id: $this->product->id,
        description: 'Test Product Line',
        quantity: 10,
        unit_price: Money::of(100, $this->company->currency->code),
        expense_account_id: $this->product->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);

    // Act: Post the vendor bill
    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Assert: NO Bill-based stock moves should be created in MANUAL mode
    $billMoves = StockMove::where('source_type', VendorBill::class)->count();
    expect($billMoves)->toBe(0);
});
