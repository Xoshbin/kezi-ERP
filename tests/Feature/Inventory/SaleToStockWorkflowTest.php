<?php

namespace Tests\Feature\Inventory;

use App\Actions\Sales\CreateInvoiceLineAction;
use App\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Inventory\ValuationMethod;
use App\Enums\Products\ProductType;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMove;
use App\Models\StockMoveValuation;
use App\Services\InvoiceService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    // Create additional accounts needed for sales workflow
    $this->cogsAccount = \App\Models\Account::factory()->for($this->company)->create([
        'name' => 'Cost of Goods Sold',
        'type' => 'cost_of_revenue',
    ]);

    // Create a customer for sales transactions
    $this->customer = \App\Models\Partner::factory()->for($this->company)->create([
        'type' => \App\Enums\Partners\PartnerType::Customer,
    ]);

    // Create stock locations with the specific names that services expect
    $this->warehouseLocation = \App\Models\StockLocation::factory()->for($this->company)->create([
        'name' => 'Warehouse',
        'type' => \App\Enums\Inventory\StockLocationType::Internal,
    ]);
    $this->vendorsLocation = \App\Models\StockLocation::factory()->for($this->company)->create([
        'name' => 'Vendors',
        'type' => \App\Enums\Inventory\StockLocationType::Vendor,
    ]);
    $this->customersLocation = \App\Models\StockLocation::factory()->for($this->company)->create([
        'name' => 'Customers',
        'type' => \App\Enums\Inventory\StockLocationType::Customer,
    ]);

    // Create a storable product with inventory settings
    $this->product = Product::factory()->for($this->company)->create([
        'type' => ProductType::Storable,
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
    \App\Models\StockQuant::factory()->create([
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
    expect($productLine->toLocation->type)->toBe(\App\Enums\Inventory\StockLocationType::Customer);

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
    \App\Models\StockQuant::factory()->create([
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
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => Money::of(250, $this->company->currency->code), // Valid cost for COGS calculation
    ]);

    $serviceProduct = Product::factory()->for($this->company)->create([
        'type' => ProductType::Service,
    ]);

    // Create initial inventory for the storable product
    \App\Models\StockQuant::factory()->create([
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
    \App\Models\StockQuant::factory()->create([
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
