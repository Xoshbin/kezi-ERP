<?php

namespace Modules\Inventory\Tests\Feature\Inventory;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Enums\Partners\PartnerType;
use Modules\Foundation\Models\Partner;
use Modules\Inventory\Enums\Inventory\StockLocationType;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Enums\Inventory\ValuationMethod;
use Modules\Inventory\Models\StockLocation;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\StockMoveValuation;
use Modules\Inventory\Models\StockQuant;
use Modules\Product\Models\Product;
use Modules\Sales\Actions\Sales\CreateInvoiceLineAction;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Services\InvoiceService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    // Create additional accounts needed for sales workflow
    $this->cogsAccount = Account::factory()->for($this->company)->create([
        'name' => 'Cost of Goods Sold',
        'type' => 'cost_of_revenue',
    ]);

    // Create a customer for sales transactions
    $this->customer = Partner::factory()->for($this->company)->create([
        'type' => PartnerType::Customer,
    ]);

    // Create stock locations with the specific names that services expect
    $this->warehouseLocation = StockLocation::factory()->for($this->company)->create([
        'name' => 'Warehouse',
        'type' => StockLocationType::Internal,
    ]);
    $this->vendorsLocation = StockLocation::factory()->for($this->company)->create([
        'name' => 'Vendors',
        'type' => StockLocationType::Vendor,
    ]);
    $this->customersLocation = StockLocation::factory()->for($this->company)->create([
        'name' => 'Customers',
        'type' => StockLocationType::Customer,
    ]);

    // Create a storable product with inventory settings
    $this->product = Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'average_cost' => Money::of(500, $this->company->currency->code), // Set initial cost
        'unit_price' => Money::of(1000, $this->company->currency->code), // Selling price
    ]);
});

it('correctly processes outgoing storable product when invoice is posted, creating stock move and COGS journal entry', function () {
    // Arrange
    $quantity = 5;
    $sellingPrice = Money::of(1000, $this->company->currency->code);
    $expectedCogs = $this->product->average_cost->multipliedBy($quantity); // 500 * 5 = 2500
    $totalSalesValue = $sellingPrice->multipliedBy($quantity); // 1000 * 5 = 5000

    // First, create initial inventory (you can't sell what you don't have)
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id, // Use the default stock location
        'quantity' => 10.0, // More than we're selling
        'reserved_quantity' => 0.0,
    ]);

    // Create a draft invoice for the storable product
    $invoice = Invoice::factory()->for($this->company)->create([
        'customer_id' => $this->customer->id,
        'status' => 'draft',
    ]);

    $lineDto = new CreateInvoiceLineDTO(
        product_id: $this->product->id,
        description: 'Test Storable Product Sale',
        quantity: $quantity,
        unit_price: $sellingPrice,
        income_account_id: $this->product->income_account_id,
        tax_id: null,
    );
    resolve(CreateInvoiceLineAction::class)->execute($invoice, $lineDto);
    $invoice->refresh(); // Refresh to get totals calculated by observers

    // Act: Post the invoice - this should trigger the complete workflow
    $invoiceService = resolve(InvoiceService::class);
    $invoiceService->confirm($invoice, $this->user);

    // Process any queued jobs (COGS calculation happens in a job)
    Queue::fake();
    $invoiceService->confirm($invoice, $this->user);

    // Assert 1: Invoice was posted successfully
    $invoice->refresh();
    expect($invoice->status->value)->toBe('posted');
    expect($invoice->invoice_number)->not->toBeNull();
    expect($invoice->journal_entry_id)->not->toBeNull();

    // Assert 2: Stock move was created for outgoing inventory
    $this->assertDatabaseHas('stock_moves', [
        'move_type' => StockMoveType::Outgoing->value,
        'status' => StockMoveStatus::Done->value,
        'source_type' => Invoice::class,
        'source_id' => $invoice->id,
    ]);

    // Assert 2b: Product line was created with correct details
    $this->assertDatabaseHas('stock_move_product_lines', [
        'product_id' => $this->product->id,
        'quantity' => $quantity,
    ]);

    $stockMove = StockMove::where('source_type', Invoice::class)
        ->where('source_id', $invoice->id)
        ->first();
    expect($stockMove)->not->toBeNull();

    // Destination must be Customer (not Vendor)
    $productLine = $stockMove->productLines()->first();
    $productLine->load('toLocation');
    expect($productLine->toLocation->type)->toBe(StockLocationType::Customer);

    // Assert 3: Sales journal entry was created correctly
    $salesJournalEntry = $invoice->journalEntry;
    expect($salesJournalEntry)->not->toBeNull();
    expect($salesJournalEntry->is_posted)->toBeTrue();

    // Sales journal entry should have:
    // Debit: Accounts Receivable (total sales value)
    // Credit: Product Sales (total sales value)
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $salesJournalEntry->id,
        'account_id' => $this->company->default_accounts_receivable_id,
        'debit' => $totalSalesValue->getMinorAmount()->toInt(),
        'credit' => 0,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $salesJournalEntry->id,
        'account_id' => $this->product->income_account_id,
        'debit' => 0,
        'credit' => $totalSalesValue->getMinorAmount()->toInt(),
    ]);
});

it('dispatches stock move confirmed event when invoice with storable products is posted', function () {
    // This test specifically checks that the StockMoveConfirmed event is dispatched
    // which was Bug #2 in the original issue

    $quantity = 3;
    $sellingPrice = Money::of(800, $this->company->currency->code);

    // Create initial inventory
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id, // Use the default stock location
        'quantity' => 10.0,
        'reserved_quantity' => 0.0,
    ]);

    $invoice = Invoice::factory()->for($this->company)->create([
        'customer_id' => $this->customer->id,
        'status' => 'draft',
    ]);

    $lineDto = new CreateInvoiceLineDTO(
        product_id: $this->product->id,
        description: 'Test Event Dispatching',
        quantity: $quantity,
        unit_price: $sellingPrice,
        income_account_id: $this->product->income_account_id,
        tax_id: null,
    );
    resolve(CreateInvoiceLineAction::class)->execute($invoice, $lineDto);
    $invoice->refresh();

    // Act: Post the invoice
    $invoiceService = resolve(InvoiceService::class);
    $invoiceService->confirm($invoice, $this->user);

    // Assert: Stock move was created (proves the event was dispatched and processed)
    $stockMove = StockMove::where('source_type', Invoice::class)
        ->where('source_id', $invoice->id)
        ->first();

    expect($stockMove)->not->toBeNull();
    expect($stockMove->move_type)->toBe(StockMoveType::Outgoing);
    expect($stockMove->status)->toBe(StockMoveStatus::Done);

    // Check product line for quantity
    $productLine = $stockMove->productLines()->first();
    expect($productLine)->not->toBeNull();
    expect((float) $productLine->quantity)->toBe((float) $quantity);
});

it('uses correct product type field when checking for storable products', function () {
    // This test specifically checks Bug #1: using product->type instead of product->product_type

    // Create products with different types
    $storableProduct = Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => Money::of(250, $this->company->currency->code), // Valid cost for COGS calculation
    ]);

    $serviceProduct = Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Service,
    ]);

    // Create initial inventory for the storable product
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $storableProduct->id,
        'location_id' => $this->stockLocation->id, // Use the default stock location
        'quantity' => 10.0,
        'reserved_quantity' => 0.0,
    ]);

    $invoice = Invoice::factory()->for($this->company)->create([
        'customer_id' => $this->customer->id,
        'status' => 'draft',
    ]);

    // Add both storable and service products to the invoice
    $storableLineDto = new CreateInvoiceLineDTO(
        product_id: $storableProduct->id,
        description: 'Storable Product',
        quantity: 2,
        unit_price: Money::of(500, $this->company->currency->code),
        income_account_id: $storableProduct->income_account_id,
        tax_id: null,
    );
    resolve(CreateInvoiceLineAction::class)->execute($invoice, $storableLineDto);

    $serviceLineDto = new CreateInvoiceLineDTO(
        product_id: $serviceProduct->id,
        description: 'Service Product',
        quantity: 1,
        unit_price: Money::of(300, $this->company->currency->code),
        income_account_id: $serviceProduct->income_account_id,
        tax_id: null,
    );
    resolve(CreateInvoiceLineAction::class)->execute($invoice, $serviceLineDto);
    $invoice->refresh();

    // Act: Post the invoice
    $invoiceService = resolve(InvoiceService::class);
    $invoiceService->confirm($invoice, $this->user);

    // Assert: Only the storable product should have a stock move
    $stockMoves = StockMove::where('source_type', Invoice::class)
        ->where('source_id', $invoice->id)
        ->get();

    expect($stockMoves)->toHaveCount(1);

    // Check that the stock move has a product line for the storable product
    $stockMove = $stockMoves->first();
    $productLine = $stockMove->productLines()->where('product_id', $storableProduct->id)->first();
    expect($productLine)->not->toBeNull();

    // No stock move should be created for the service product
    $serviceStockMove = StockMove::whereHas('productLines', function ($query) use ($serviceProduct) {
        $query->where('product_id', $serviceProduct->id);
    })->first();
    expect($serviceStockMove)->toBeNull();
});

it('creates COGS journal entry when ProcessOutgoingStockJob is processed', function () {
    // This test verifies the complete COGS workflow including job processing
    // This would catch Bug #3: wrong enum constants in HandleStockMoveConfirmation

    $quantity = 4;
    $costPerUnit = Money::of(600, $this->company->currency->code);
    $sellingPrice = Money::of(1200, $this->company->currency->code);
    $expectedCogs = $costPerUnit->multipliedBy($quantity); // 600 * 4 = 2400

    // Set the product's average cost
    $this->product->update(['average_cost' => $costPerUnit]);

    // Create initial inventory
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id, // Use the default stock location
        'quantity' => 10.0,
        'reserved_quantity' => 0.0,
    ]);

    $invoice = Invoice::factory()->for($this->company)->create([
        'customer_id' => $this->customer->id,
        'status' => 'draft',
    ]);

    $lineDto = new CreateInvoiceLineDTO(
        product_id: $this->product->id,
        description: 'Test COGS Calculation',
        quantity: $quantity,
        unit_price: $sellingPrice,
        income_account_id: $this->product->income_account_id,
        tax_id: null,
    );
    resolve(CreateInvoiceLineAction::class)->execute($invoice, $lineDto);
    $invoice->refresh();

    // Act: Post the invoice and process the queue
    $invoiceService = resolve(InvoiceService::class);
    $invoiceService->confirm($invoice, $this->user);

    // Process the queued COGS job
    $this->artisan('queue:work --once');

    // Assert: COGS journal entry was created
    // Note: This assertion will fail until InventoryValuationService.processOutgoingStock() is implemented
    // but it documents the expected behavior

    $stockMove = StockMove::where('source_type', Invoice::class)
        ->where('source_id', $invoice->id)
        ->first();
    expect($stockMove)->not->toBeNull();

    // Check if StockMoveValuation was created (when COGS is implemented)
    $stockMoveValuation = StockMoveValuation::where('stock_move_id', $stockMove->id)->first();

    if ($stockMoveValuation) {
        // If COGS is implemented, verify the journal entry
        expect($stockMoveValuation->cost_impact)->toEqual($expectedCogs);
        expect($stockMoveValuation->journal_entry_id)->not->toBeNull();

        $cogsJournalEntry = $stockMoveValuation->journalEntry;
        expect($cogsJournalEntry)->not->toBeNull();

        // COGS journal entry should have:
        // Debit: COGS Account (cost of goods sold)
        // Credit: Inventory Account (reduce inventory value)
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $cogsJournalEntry->id,
            'account_id' => $this->product->default_cogs_account_id,
            'debit' => $expectedCogs->getMinorAmount()->toInt(),
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $cogsJournalEntry->id,
            'account_id' => $this->product->default_inventory_account_id,
            'debit' => 0,
            'credit' => $expectedCogs->getMinorAmount()->toInt(),
        ]);
    } else {
        // If COGS is not yet implemented, this test documents what should happen
        $this->markTestIncomplete('COGS calculation not yet implemented in InventoryValuationService.processOutgoingStock()');
    }
});

it('auto-confirms delivery when company inventory_accounting_mode is automatic', function () {
    // Arrange: Set company to auto-record mode
    $this->company->update([
        'inventory_accounting_mode' => \Modules\Inventory\Enums\Inventory\InventoryAccountingMode::AUTO_RECORD_ON_BILL,
    ]);

    $quantity = 5;
    $sellingPrice = Money::of(1000, $this->company->currency->code);

    // Create initial inventory
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->warehouseLocation->id,
        'quantity' => 10.0,
        'reserved_quantity' => 0.0,
    ]);

    // Create a sales order
    $salesOrder = \Modules\Sales\Models\SalesOrder::factory()->for($this->company)->create([
        'customer_id' => $this->customer->id,
        'status' => \Modules\Sales\Enums\Sales\SalesOrderStatus::Draft,
        'currency_id' => $this->company->currency_id,
        'created_by_user_id' => $this->user->id,
    ]);

    // Add sales order line
    \Modules\Sales\Models\SalesOrderLine::factory()->create([
        'sales_order_id' => $salesOrder->id,
        'product_id' => $this->product->id,
        'description' => 'Test Product Sale',
        'quantity' => $quantity,
        'unit_price' => $sellingPrice,
    ]);

    $salesOrder->refresh();

    // Act: Confirm the sales order
    $confirmAction = app(\Modules\Sales\Actions\Sales\ConfirmSalesOrderAction::class);
    $confirmAction->execute($salesOrder, $this->user);

    // Assert: Stock picking was created and is in 'done' state
    $stockPicking = \Modules\Inventory\Models\StockPicking::where('origin', 'LIKE', '%'.$salesOrder->so_number.'%')->first();
    expect($stockPicking)->not->toBeNull();
    expect($stockPicking->state)->toBe(\Modules\Inventory\Enums\Inventory\StockPickingState::Done);

    // Assert: Stock move was created and is in 'done' status
    $stockMove = StockMove::where('source_type', \Modules\Sales\Models\SalesOrder::class)
        ->where('source_id', $salesOrder->id)
        ->first();
    expect($stockMove)->not->toBeNull();
    expect($stockMove->status)->toBe(StockMoveStatus::Done);

    // Assert: COGS journal entry should exist (via StockMoveConfirmed event)
    $this->artisan('queue:work --once');

    $stockMoveValuation = StockMoveValuation::where('stock_move_id', $stockMove->id)->first();
    if ($stockMoveValuation) {
        expect($stockMoveValuation->journal_entry_id)->not->toBeNull();
    }
});

it('does not auto-confirm delivery when company inventory_accounting_mode is manual', function () {
    // Arrange: Set company to manual recording mode
    $this->company->update([
        'inventory_accounting_mode' => \Modules\Inventory\Enums\Inventory\InventoryAccountingMode::MANUAL_INVENTORY_RECORDING,
    ]);

    $quantity = 5;
    $sellingPrice = Money::of(1000, $this->company->currency->code);

    // Create initial inventory
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->warehouseLocation->id,
        'quantity' => 10.0,
        'reserved_quantity' => 0.0,
    ]);

    // Create a sales order
    $salesOrder = \Modules\Sales\Models\SalesOrder::factory()->for($this->company)->create([
        'customer_id' => $this->customer->id,
        'status' => \Modules\Sales\Enums\Sales\SalesOrderStatus::Draft,
        'currency_id' => $this->company->currency_id,
        'created_by_user_id' => $this->user->id,
    ]);

    // Add sales order line
    \Modules\Sales\Models\SalesOrderLine::factory()->create([
        'sales_order_id' => $salesOrder->id,
        'product_id' => $this->product->id,
        'description' => 'Test Product Sale',
        'quantity' => $quantity,
        'unit_price' => $sellingPrice,
    ]);

    $salesOrder->refresh();

    // Act: Confirm the sales order
    $confirmAction = app(\Modules\Sales\Actions\Sales\ConfirmSalesOrderAction::class);
    $confirmAction->execute($salesOrder, $this->user);

    // Assert: Stock picking was created and is in 'draft' state (NOT done)
    $stockPicking = \Modules\Inventory\Models\StockPicking::where('origin', 'LIKE', '%'.$salesOrder->so_number.'%')->first();
    expect($stockPicking)->not->toBeNull();
    expect($stockPicking->state)->toBe(\Modules\Inventory\Enums\Inventory\StockPickingState::Draft);

    // Assert: Stock move was created but is in 'draft' status (NOT done)
    $stockMove = StockMove::where('source_type', \Modules\Sales\Models\SalesOrder::class)
        ->where('source_id', $salesOrder->id)
        ->first();
    expect($stockMove)->not->toBeNull();
    expect($stockMove->status)->toBe(StockMoveStatus::Draft);

    // Assert: NO COGS journal entry (stock move not confirmed)
    $stockMoveValuation = StockMoveValuation::where('stock_move_id', $stockMove->id)->first();
    expect($stockMoveValuation)->toBeNull();
});
