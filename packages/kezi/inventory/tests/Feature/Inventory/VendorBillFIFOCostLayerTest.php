<?php

namespace Kezi\Inventory\Tests\Feature\Inventory;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Kezi\Inventory\Enums\Inventory\InventoryAccountingMode;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Inventory\Events\Inventory\StockMoveConfirmed;
use Kezi\Inventory\Models\InventoryCostLayer;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveValuation;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Actions\Purchases\CreateVendorBillLineAction;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Services\VendorBillService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    // Set company to AUTO_RECORD_ON_BILL mode (Mode 1)
    $this->company->update([
        'inventory_accounting_mode' => InventoryAccountingMode::AUTO_RECORD_ON_BILL,
    ]);
});

describe('VendorBill FIFO Cost Layer Creation', function () {
    it('creates cost layer when vendor bill with FIFO product is posted', function () {
        // Arrange: Create FIFO product
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::Fifo,
            'quantity_on_hand' => 0,
            'average_cost' => Money::of(0, $this->company->currency->code),
            'default_inventory_account_id' => $this->inventoryAccount->id,
            'default_stock_input_account_id' => $this->stockInputAccount->id,
            'default_cogs_account_id' => $this->cogsAccount->id,
        ]);

        // Create vendor bill
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
        ]);

        $lineDto = new CreateVendorBillLineDTO(
            product_id: $product->id,
            description: 'FIFO Product Purchase',
            quantity: 10,
            unit_price: Money::of(1500, $this->company->currency->code),
            expense_account_id: $product->expense_account_id,
            tax_id: null,
            analytic_account_id: null,
        );
        app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);

        // Act: Post vendor bill
        app(VendorBillService::class)->post($vendorBill, $this->user);

        // Assert: Cost layer was created
        $costLayers = InventoryCostLayer::where('product_id', $product->id)->get();
        expect($costLayers)->toHaveCount(1);

        $costLayer = $costLayers->first();
        expect($costLayer->quantity)->toBe(10.0);
        expect($costLayer->remaining_quantity)->toBe(10.0);
        expect($costLayer->cost_per_unit)->toEqual(Money::of(1500, $this->company->currency->code));
        expect($costLayer->source_type)->toBe(VendorBill::class);
        expect($costLayer->source_id)->toBe($vendorBill->id);
    });

    it('creates cost layer when vendor bill with LIFO product is posted', function () {
        // Arrange: Create LIFO product
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::Lifo,
            'quantity_on_hand' => 0,
            'average_cost' => Money::of(0, $this->company->currency->code),
            'default_inventory_account_id' => $this->inventoryAccount->id,
            'default_stock_input_account_id' => $this->stockInputAccount->id,
            'default_cogs_account_id' => $this->cogsAccount->id,
        ]);

        // Create vendor bill
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
        ]);

        $lineDto = new CreateVendorBillLineDTO(
            product_id: $product->id,
            description: 'LIFO Product Purchase',
            quantity: 5,
            unit_price: Money::of(2000, $this->company->currency->code),
            expense_account_id: $product->expense_account_id,
            tax_id: null,
            analytic_account_id: null,
        );
        app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);

        // Act: Post vendor bill
        app(VendorBillService::class)->post($vendorBill, $this->user);

        // Assert: Cost layer was created
        $costLayers = InventoryCostLayer::where('product_id', $product->id)->get();
        expect($costLayers)->toHaveCount(1);

        $costLayer = $costLayers->first();
        expect($costLayer->quantity)->toBe(5.0);
        expect($costLayer->cost_per_unit)->toEqual(Money::of(2000, $this->company->currency->code));
    });

    it('updates average cost for AVCO product without creating cost layer', function () {
        // Arrange: Create AVCO product with existing average cost
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::Avco,
            'quantity_on_hand' => 0,
            'average_cost' => Money::of(0, $this->company->currency->code),
            'default_inventory_account_id' => $this->inventoryAccount->id,
            'default_stock_input_account_id' => $this->stockInputAccount->id,
            'default_cogs_account_id' => $this->cogsAccount->id,
        ]);

        // Create vendor bill
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
        ]);

        $lineDto = new CreateVendorBillLineDTO(
            product_id: $product->id,
            description: 'AVCO Product Purchase',
            quantity: 10,
            unit_price: Money::of(1000, $this->company->currency->code),
            expense_account_id: $product->expense_account_id,
            tax_id: null,
            analytic_account_id: null,
        );
        app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);

        // Act: Post vendor bill
        app(VendorBillService::class)->post($vendorBill, $this->user);

        // Assert: No cost layer created
        expect(InventoryCostLayer::where('product_id', $product->id)->count())->toBe(0);

        // Assert: Average cost was updated
        $product->refresh();
        expect($product->average_cost)->toEqual(Money::of(1000, $this->company->currency->code));
    });
});

describe('Multiple VendorBill Cost Layer Order', function () {
    it('creates multiple cost layers with correct order for FIFO', function () {
        // Arrange: Create FIFO product
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::Fifo,
            'quantity_on_hand' => 0,
            'average_cost' => Money::of(0, $this->company->currency->code),
            'default_inventory_account_id' => $this->inventoryAccount->id,
            'default_stock_input_account_id' => $this->stockInputAccount->id,
            'default_cogs_account_id' => $this->cogsAccount->id,
        ]);

        // Create first vendor bill
        $vendorBill1 = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->subDays(2)->format('Y-m-d'),
            'accounting_date' => now()->subDays(2)->format('Y-m-d'),
        ]);

        $lineDto1 = new CreateVendorBillLineDTO(
            product_id: $product->id,
            description: 'First Purchase',
            quantity: 10,
            unit_price: Money::of(1000, $this->company->currency->code),
            expense_account_id: $product->expense_account_id,
            tax_id: null,
            analytic_account_id: null,
        );
        app(CreateVendorBillLineAction::class)->execute($vendorBill1, $lineDto1);
        app(VendorBillService::class)->post($vendorBill1, $this->user);

        // Create second vendor bill
        $vendorBill2 = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
        ]);

        $lineDto2 = new CreateVendorBillLineDTO(
            product_id: $product->id,
            description: 'Second Purchase',
            quantity: 5,
            unit_price: Money::of(2000, $this->company->currency->code),
            expense_account_id: $product->expense_account_id,
            tax_id: null,
            analytic_account_id: null,
        );
        app(CreateVendorBillLineAction::class)->execute($vendorBill2, $lineDto2);
        app(VendorBillService::class)->post($vendorBill2, $this->user);

        // Assert: Two cost layers created in correct order
        $costLayers = InventoryCostLayer::where('product_id', $product->id)
            ->orderBy('created_at', 'asc')
            ->get();

        expect($costLayers)->toHaveCount(2);

        // First layer (older, lower cost)
        expect($costLayers[0]->quantity)->toBe(10.0);
        expect($costLayers[0]->cost_per_unit)->toEqual(Money::of(1000, $this->company->currency->code));

        // Second layer (newer, higher cost)
        expect($costLayers[1]->quantity)->toBe(5.0);
        expect($costLayers[1]->cost_per_unit)->toEqual(Money::of(2000, $this->company->currency->code));
    });
});

describe('StockMoveConfirmed Event', function () {
    it('does NOT dispatch StockMoveConfirmed event for vendor bill stock moves to prevent duplicate processing', function () {
        Event::fake([StockMoveConfirmed::class]);

        // Arrange: Create storable product
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::Fifo,
            'quantity_on_hand' => 0,
            'average_cost' => Money::of(0, $this->company->currency->code),
            'default_inventory_account_id' => $this->inventoryAccount->id,
            'default_stock_input_account_id' => $this->stockInputAccount->id,
            'default_cogs_account_id' => $this->cogsAccount->id,
        ]);

        // Create vendor bill
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
        ]);

        $lineDto = new CreateVendorBillLineDTO(
            product_id: $product->id,
            description: 'Test Product',
            quantity: 5,
            unit_price: Money::of(1000, $this->company->currency->code),
            expense_account_id: $product->expense_account_id,
            tax_id: null,
            analytic_account_id: null,
        );
        app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);

        // Act: Post vendor bill
        app(VendorBillService::class)->post($vendorBill, $this->user);

        // Assert: StockMoveConfirmed event was NOT dispatched
        // (valuation is handled directly by createConsolidatedIncomingStockJournalEntry)
        Event::assertNotDispatched(StockMoveConfirmed::class);
    });
});

describe('Anglo-Saxon Accounting Journal Entries', function () {
    it('creates correct journal entries following Anglo-Saxon accounting', function () {
        // Arrange: Create FIFO product
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::Fifo,
            'quantity_on_hand' => 0,
            'average_cost' => Money::of(0, $this->company->currency->code),
            'default_inventory_account_id' => $this->inventoryAccount->id,
            'default_stock_input_account_id' => $this->stockInputAccount->id,
            'default_cogs_account_id' => $this->cogsAccount->id,
        ]);

        // Create vendor bill
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
        ]);

        $lineDto = new CreateVendorBillLineDTO(
            product_id: $product->id,
            description: 'Test Product',
            quantity: 10,
            unit_price: Money::of(1000, $this->company->currency->code),
            expense_account_id: $product->expense_account_id,
            tax_id: null,
            analytic_account_id: null,
        );
        app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);

        // Act: Post vendor bill
        app(VendorBillService::class)->post($vendorBill, $this->user);

        $vendorBill->refresh();
        $totalAmount = Money::of(10000, $this->company->currency->code); // 10 * 1000

        // Assert: Vendor Bill JE has Stock Input Dr / AP Cr
        $billJE = $vendorBill->journalEntry;
        expect($billJE)->not->toBeNull();

        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $billJE->id,
            'account_id' => $this->stockInputAccount->id,
            'debit' => $totalAmount->getMinorAmount()->toInt(),
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $billJE->id,
            'account_id' => $this->company->default_accounts_payable_id,
            'debit' => 0,
            'credit' => $totalAmount->getMinorAmount()->toInt(),
        ]);

        // Assert: Inventory Valuation JE has Inventory Dr / Stock Input Cr
        $stockMove = StockMove::where('source_type', VendorBill::class)
            ->where('source_id', $vendorBill->id)
            ->first();

        $valuation = StockMoveValuation::where('stock_move_id', $stockMove->id)->first();
        expect($valuation)->not->toBeNull();

        $inventoryJE = $valuation->journalEntry;
        expect($inventoryJE)->not->toBeNull();

        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $inventoryJE->id,
            'account_id' => $this->inventoryAccount->id,
            'debit' => $totalAmount->getMinorAmount()->toInt(),
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $inventoryJE->id,
            'account_id' => $this->stockInputAccount->id,
            'debit' => 0,
            'credit' => $totalAmount->getMinorAmount()->toInt(),
        ]);
    });
});
