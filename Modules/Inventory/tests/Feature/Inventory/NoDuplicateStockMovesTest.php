<?php

namespace Modules\Inventory\Tests\Feature\Inventory;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Foundation\Models\Partner;
use Modules\Inventory\Enums\Inventory\InventoryAccountingMode;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\ValuationMethod;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\StockPicking;
use Modules\Product\Models\Product;
use Modules\Purchase\Actions\Purchases\CreateVendorBillLineAction;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Modules\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Services\PurchaseOrderService;
use Modules\Purchase\Services\VendorBillService;
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
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
    ]);
});

it('does NOT create PO-based stock moves in AUTO_RECORD_ON_BILL mode', function () {
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

    // Assert: NO stock moves should be created from PO
    $poMoves = StockMove::where('source_type', PurchaseOrderLine::class)->count();
    expect($poMoves)->toBe(0);

    // Assert: NO stock pickings should be created from PO
    $poPicking = StockPicking::where('origin', $po->po_number)->first();
    expect($poPicking)->toBeNull();
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

    // Verify NO stock moves from PO
    $poMoves = StockMove::where('source_type', PurchaseOrderLine::class)->count();
    expect($poMoves)->toBe(0);

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

    // Assert: ONLY Bill-based stock moves should exist
    $billMoves = StockMove::where('source_type', VendorBill::class)->count();
    expect($billMoves)->toBe(1);

    // Assert: Still NO PO-based stock moves
    $poMovesAfter = StockMove::where('source_type', PurchaseOrderLine::class)->count();
    expect($poMovesAfter)->toBe(0);

    // Assert: Total stock moves should be exactly 1 (no duplicates)
    $totalMoves = StockMove::count();
    expect($totalMoves)->toBe(1);
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
