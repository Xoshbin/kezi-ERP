<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use Kezi\Foundation\Models\Partner;
use Kezi\Inventory\Enums\Inventory\InventoryAccountingMode;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Inventory\Models\InventoryCostLayer;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveValuation;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
    $this->actingAs($this->user);

    // Set company to AUTO_RECORD_ON_BILL mode
    $this->company->update([
        'inventory_accounting_mode' => InventoryAccountingMode::AUTO_RECORD_ON_BILL,
    ]);
});

it('creates FIFO cost layer when posting vendor bill via Filament action', function () {
    // Arrange: vendor and FIFO product
    $vendor = Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::Fifo,
        'quantity_on_hand' => 0,
        'average_cost' => Money::of(0, $this->company->currency->code),
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
    ]);

    // Create bill + line
    $qty = 10;
    $unit = Money::of(125, $this->company->currency->code);
    $subtotal = $unit->multipliedBy($qty);

    /** @var VendorBill $vendorBill */
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $vendor->id,
        'currency_id' => $this->company->currency_id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now()->format('Y-m-d'),
        'accounting_date' => now()->format('Y-m-d'),
        'total_amount' => $subtotal,
        'total_tax' => Money::of(0, $this->company->currency->code),
    ]);

    $vendorBill->lines()->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'description' => 'FIFO inventory purchase',
        'quantity' => $qty,
        'unit_price' => $unit,
        'subtotal' => $subtotal,
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'expense_account_id' => $product->expense_account_id,
    ]);

    $vendorBill->refresh();

    // Act: Post via Filament action
    livewire(EditVendorBill::class, [
        'record' => $vendorBill->getRouteKey(),
    ])
        ->assertActionVisible('post')
        ->callAction('post')
        ->assertHasNoActionErrors()
        ->assertNotified();

    // Assert: Bill posted successfully
    $vendorBill->refresh();
    expect($vendorBill->status)->toBe(VendorBillStatus::Posted);
    expect($vendorBill->journalEntry)->not->toBeNull();

    // Assert: Stock move created (Incoming, Done)
    $this->assertDatabaseHas('stock_moves', [
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming->value,
        'status' => StockMoveStatus::Done->value,
        'source_type' => VendorBill::class,
        'source_id' => $vendorBill->id,
    ]);

    // Assert: FIFO cost layer created with correct values
    $costLayers = InventoryCostLayer::where('product_id', $product->id)->get();
    expect($costLayers)->toHaveCount(1);

    $costLayer = $costLayers->first();
    expect($costLayer->quantity)->toBe(10.0);
    expect($costLayer->remaining_quantity)->toBe(10.0);
    expect($costLayer->cost_per_unit)->toEqual($unit);
    expect($costLayer->source_type)->toBe(VendorBill::class);
    expect($costLayer->source_id)->toBe($vendorBill->id);
});

it('creates LIFO cost layer when posting vendor bill via Filament action', function () {
    // Arrange: vendor and LIFO product
    $vendor = Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::Lifo,
        'quantity_on_hand' => 0,
        'average_cost' => Money::of(0, $this->company->currency->code),
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
    ]);

    $qty = 5;
    $unit = Money::of(200, $this->company->currency->code);
    $subtotal = $unit->multipliedBy($qty);

    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $vendor->id,
        'currency_id' => $this->company->currency_id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now()->format('Y-m-d'),
        'accounting_date' => now()->format('Y-m-d'),
        'total_amount' => $subtotal,
        'total_tax' => Money::of(0, $this->company->currency->code),
    ]);

    $vendorBill->lines()->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'description' => 'LIFO inventory purchase',
        'quantity' => $qty,
        'unit_price' => $unit,
        'subtotal' => $subtotal,
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'expense_account_id' => $product->expense_account_id,
    ]);

    $vendorBill->refresh();

    // Act: Post via Filament
    livewire(EditVendorBill::class, [
        'record' => $vendorBill->getRouteKey(),
    ])
        ->callAction('post')
        ->assertNotified();

    // Assert: LIFO cost layer created
    $costLayers = InventoryCostLayer::where('product_id', $product->id)->get();
    expect($costLayers)->toHaveCount(1);
    expect($costLayers->first()->cost_per_unit)->toEqual($unit);
});

it('creates proper Anglo-Saxon journal entries when posting vendor bill with FIFO product', function () {
    // Arrange
    $vendor = Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::Fifo,
        'quantity_on_hand' => 0,
        'average_cost' => Money::of(0, $this->company->currency->code),
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
    ]);

    $qty = 10;
    $unit = Money::of(100, $this->company->currency->code);
    $subtotal = $unit->multipliedBy($qty); // 1000

    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $vendor->id,
        'currency_id' => $this->company->currency_id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now()->format('Y-m-d'),
        'accounting_date' => now()->format('Y-m-d'),
        'total_amount' => $subtotal,
        'total_tax' => Money::of(0, $this->company->currency->code),
    ]);

    $vendorBill->lines()->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'description' => 'FIFO product for JE test',
        'quantity' => $qty,
        'unit_price' => $unit,
        'subtotal' => $subtotal,
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'expense_account_id' => $product->expense_account_id,
    ]);

    $vendorBill->refresh();

    // Act: Post via Filament
    livewire(EditVendorBill::class, [
        'record' => $vendorBill->getRouteKey(),
    ])
        ->callAction('post')
        ->assertNotified();

    $vendorBill->refresh();
    $amountMinor = $subtotal->getMinorAmount()->toInt();

    // Assert 1: Vendor Bill JE has Stock Input Dr / AP Cr
    $billJE = $vendorBill->journalEntry;
    expect($billJE)->not->toBeNull();

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $billJE->id,
        'account_id' => $this->stockInputAccount->id,
        'debit' => $amountMinor,
        'credit' => 0,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $billJE->id,
        'account_id' => $this->company->default_accounts_payable_id,
        'debit' => 0,
        'credit' => $amountMinor,
    ]);

    // Assert 2: Inventory Valuation JE has Inventory Dr / Stock Input Cr
    $stockMove = StockMove::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->first();
    expect($stockMove)->not->toBeNull();

    $valuation = StockMoveValuation::where('stock_move_id', $stockMove->id)->first();
    expect($valuation)->not->toBeNull();

    $inventoryJE = $valuation->journalEntry;
    expect($inventoryJE)->not->toBeNull();

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $inventoryJE->id,
        'account_id' => $this->inventoryAccount->id,
        'debit' => $amountMinor,
        'credit' => 0,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $inventoryJE->id,
        'account_id' => $this->stockInputAccount->id,
        'debit' => 0,
        'credit' => $amountMinor,
    ]);
});
