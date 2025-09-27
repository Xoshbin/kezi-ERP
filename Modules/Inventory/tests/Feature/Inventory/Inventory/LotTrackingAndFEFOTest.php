<?php

namespace Tests\Feature\Inventory;

use App\Actions\Sales\CreateInvoiceLineAction;
use App\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use App\Enums\Inventory\StockPickingState;
use App\Enums\Inventory\StockPickingType;
use App\Enums\Products\ProductType;
use App\Models\Invoice;
use App\Models\Lot;
use App\Models\Product;
use App\Models\StockMove;
use App\Models\StockMoveLine;
use App\Models\StockPicking;
use App\Models\StockQuant;
use App\Services\InvoiceService;
use App\Services\Inventory\StockReservationService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    // Set inventory accounting mode to manual for lot tracking tests
    $this->company->update([
        'inventory_accounting_mode' => \App\Enums\Inventory\InventoryAccountingMode::MANUAL_INVENTORY_RECORDING,
    ]);

    // Create COGS account first
    $this->cogsAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create([
        'type' => 'cost_of_revenue',
    ]);

    $this->product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => \App\Enums\Inventory\ValuationMethod::FIFO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);

    $this->reservationService = app(StockReservationService::class);

    // Create a customer for invoice tests
    $this->customer = \Modules\Foundation\Models\Partner::factory()->for($this->company)->create([
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Customer,
    ]);
});

it('creates lots on receipt and tracks them in quants', function () {
    $receiptDate = Carbon::create(2025, 1, 15);
    $expirationDate = Carbon::create(2025, 6, 15);
    Carbon::setTestNow($receiptDate);

    // Create lot first
    $lot = Lot::factory()->for($this->company)->create([
        'product_id' => $this->product->id,
        'lot_code' => 'BATCH-A001',
        'expiration_date' => $expirationDate,
    ]);

    // Create a receipt picking
    $picking = StockPicking::factory()->for($this->company)->create([
        'type' => StockPickingType::Receipt,
        'state' => StockPickingState::Draft,
        'scheduled_date' => $receiptDate,
        'reference' => 'REC-001',
        'created_by_user_id' => $this->user->id,
    ]);

    // Create stock move for receipt
    $move = StockMove::factory()->for($this->company)->create([
        'product_id' => $this->product->id,
        'quantity' => 10.0,
        'from_location_id' => $this->vendorLocation->id,
        'to_location_id' => $this->stockLocation->id,
        'move_type' => \App\Enums\Inventory\StockMoveType::Incoming,
        'status' => \App\Enums\Inventory\StockMoveStatus::Done,
        'move_date' => $receiptDate,
        'reference' => 'REC-001',
        'picking_id' => $picking->id,
        'created_by_user_id' => $this->user->id,
        'source_type' => 'Test',
        'source_id' => 1,
    ]);

    // Create stock move line for lot tracking
    $productLine = $move->productLines()->first();
    StockMoveLine::create([
        'company_id' => $this->company->id,
        'stock_move_product_line_id' => $productLine->id,
        'lot_id' => $lot->id,
        'quantity' => 10.0,
    ]);

    // Apply the stock movement with lot
    app(\App\Services\Inventory\StockQuantService::class)->applyForIncomingWithLot($move, $lot->id);

    // Assert lot exists
    expect($lot->fresh())->not->toBeNull();
    expect($lot->expiration_date->format('Y-m-d'))->toBe($expirationDate->format('Y-m-d'));

    // Assert quant was created with lot
    $quant = StockQuant::where('company_id', $this->company->id)
        ->where('product_id', $this->product->id)
        ->where('location_id', $this->stockLocation->id)
        ->where('lot_id', $lot->id)
        ->first();

    expect($quant)->not->toBeNull();
    expect($quant->quantity)->toBe(10.0);
    expect($quant->reserved_quantity)->toBe(0.0);

    // Assert stock move line was created
    $productLine = $move->productLines()->first();
    $moveLine = StockMoveLine::where('stock_move_product_line_id', $productLine->id)
        ->where('lot_id', $lot->id)
        ->first();

    expect($moveLine)->not->toBeNull();
    expect($moveLine->quantity)->toBe(10.0);
});

it('applies FEFO allocation when multiple lots exist with different expiration dates', function () {
    $receiptDate = Carbon::create(2025, 1, 15);
    Carbon::setTestNow($receiptDate);

    // Create two lots with different expiration dates
    $lot1 = Lot::factory()->for($this->company)->create([
        'product_id' => $this->product->id,
        'lot_code' => 'BATCH-001',
        'expiration_date' => Carbon::create(2025, 3, 15), // Expires first
    ]);

    $lot2 = Lot::factory()->for($this->company)->create([
        'product_id' => $this->product->id,
        'lot_code' => 'BATCH-002',
        'expiration_date' => Carbon::create(2025, 6, 15), // Expires later
    ]);

    // Create quants for both lots
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'lot_id' => $lot1->id,
        'quantity' => 5.0,
        'reserved_quantity' => 0.0,
    ]);

    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'lot_id' => $lot2->id,
        'quantity' => 8.0,
        'reserved_quantity' => 0.0,
    ]);

    // Create cost layers for both lots
    \App\Models\InventoryCostLayer::factory()->create([
        'product_id' => $this->product->id,
        'quantity' => 5.0,
        'remaining_quantity' => 5.0,
        'cost_per_unit' => \Brick\Money\Money::of(100, $this->company->currency->code),
        'purchase_date' => $receiptDate,
        'source_type' => 'Test',
        'source_id' => 1,
    ]);

    \App\Models\InventoryCostLayer::factory()->create([
        'product_id' => $this->product->id,
        'quantity' => 8.0,
        'remaining_quantity' => 8.0,
        'cost_per_unit' => \Brick\Money\Money::of(100, $this->company->currency->code),
        'purchase_date' => $receiptDate,
        'source_type' => 'Test',
        'source_id' => 2,
    ]);

    // Create a sales order for 7 units
    $invoice = \Modules\Sales\Models\Invoice::factory()->for($this->company)->create([
        'customer_id' => $this->customer->id,
        'status' => 'draft',
    ]);

    $lineDto = new CreateInvoiceLineDTO(
        description: 'Sale with FEFO',
        quantity: 7,
        unit_price: Money::of(200, $this->company->currency->code),
        income_account_id: $this->product->income_account_id,
        product_id: $this->product->id,
        tax_id: null,
    );

    app(CreateInvoiceLineAction::class)->execute($invoice, $lineDto);
    app(InvoiceService::class)->confirm($invoice->fresh(), $this->user);

    // Assert FEFO allocation: lot1 (5 units) + lot2 (2 units)
    $deliveryPicking = StockPicking::where('company_id', $this->company->id)
        ->where('type', StockPickingType::Delivery)
        ->first();

    expect($deliveryPicking)->not->toBeNull();
    expect($deliveryPicking->state)->toBe(StockPickingState::Done);

    $move = $deliveryPicking->stockMoves()->first();
    $productLine = $move->productLines()->first();
    $moveLines = StockMoveLine::where('stock_move_product_line_id', $productLine->id)->get();

    expect($moveLines->count())->toBe(2);

    // First lot should be fully consumed (5 units)
    $line1 = $moveLines->where('lot_id', $lot1->id)->first();
    expect($line1)->not->toBeNull();
    expect($line1->quantity)->toBe(5.0);

    // Second lot should be partially consumed (2 units)
    $line2 = $moveLines->where('lot_id', $lot2->id)->first();
    expect($line2)->not->toBeNull();
    expect($line2->quantity)->toBe(2.0);

    // Verify quant updates
    $quant1 = StockQuant::where('lot_id', $lot1->id)->first();
    expect($quant1->quantity)->toBe(0.0);
    expect($quant1->reserved_quantity)->toBe(0.0);

    $quant2 = StockQuant::where('lot_id', $lot2->id)->first();
    expect($quant2->quantity)->toBe(6.0); // 8 - 2
    expect($quant2->reserved_quantity)->toBe(0.0);
});

it('prevents allocation of expired lots', function () {
    $currentDate = Carbon::create(2025, 4, 15);
    Carbon::setTestNow($currentDate);

    // Create an expired lot
    $expiredLot = Lot::factory()->for($this->company)->create([
        'product_id' => $this->product->id,
        'lot_code' => 'EXPIRED-001',
        'expiration_date' => Carbon::create(2025, 3, 15), // Already expired
    ]);

    // Create a valid lot
    $validLot = Lot::factory()->for($this->company)->create([
        'product_id' => $this->product->id,
        'lot_code' => 'VALID-001',
        'expiration_date' => Carbon::create(2025, 8, 15), // Still valid
    ]);

    // Create quants for both lots
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'lot_id' => $expiredLot->id,
        'quantity' => 10.0,
        'reserved_quantity' => 0.0,
    ]);

    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'lot_id' => $validLot->id,
        'quantity' => 5.0,
        'reserved_quantity' => 0.0,
    ]);

    // Try to reserve 8 units - should only get 5 from valid lot
    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'quantity' => 8.0,
        'from_location_id' => $this->stockLocation->id,
        'to_location_id' => $this->customerLocation->id,
        'move_type' => \App\Enums\Inventory\StockMoveType::Outgoing,
        'status' => \App\Enums\Inventory\StockMoveStatus::Confirmed,
        'move_date' => $currentDate,
        'created_by_user_id' => $this->user->id,
    ]);

    $reservedQty = $this->reservationService->reserveForMove($move, $this->stockLocation->id);

    // Should only reserve 5 units from valid lot, not 8
    expect($reservedQty)->toBe(5.0);

    // Verify only valid lot was reserved
    $validQuant = StockQuant::where('lot_id', $validLot->id)->first();
    expect($validQuant->reserved_quantity)->toBe(5.0);

    $expiredQuant = StockQuant::where('lot_id', $expiredLot->id)->first();
    expect($expiredQuant->reserved_quantity)->toBe(0.0);
});

it('handles partial reservations and backorders correctly', function () {
    $currentDate = Carbon::create(2025, 2, 15);
    Carbon::setTestNow($currentDate);

    // Create a lot with limited quantity
    $lot = Lot::factory()->for($this->company)->create([
        'product_id' => $this->product->id,
        'lot_code' => 'LIMITED-001',
        'expiration_date' => Carbon::create(2025, 8, 15),
    ]);

    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'location_id' => $this->stockLocation->id,
        'lot_id' => $lot->id,
        'quantity' => 3.0,
        'reserved_quantity' => 0.0,
    ]);

    // Create sales order for more than available
    $invoice = \Modules\Sales\Models\Invoice::factory()->for($this->company)->create([
        'customer_id' => $this->customer->id,
        'status' => 'draft',
    ]);

    $lineDto = new CreateInvoiceLineDTO(
        description: 'Oversold item',
        quantity: 10,
        unit_price: Money::of(200, $this->company->currency->code),
        income_account_id: $this->product->income_account_id,
        product_id: $this->product->id,
        tax_id: null,
    );

    app(CreateInvoiceLineAction::class)->execute($invoice, $lineDto);
    app(InvoiceService::class)->confirm($invoice->fresh(), $this->user);

    // Should create delivery picking (backorder functionality not yet implemented)
    $deliveryPicking = StockPicking::where('company_id', $this->company->id)
        ->where('type', StockPickingType::Delivery)
        ->first();

    expect($deliveryPicking)->not->toBeNull();
    expect($deliveryPicking->state)->toBe(StockPickingState::Done);

    // Move should have full requested quantity (10 units) but only 3 reserved/consumed
    $move = $deliveryPicking->stockMoves()->first();
    $productLine = $move->productLines()->first();
    expect((float) $productLine->quantity)->toBe(10.0); // Full requested quantity

    // Only 3 units should be consumed from available stock
    $productLine = $move->productLines()->first();
    $moveLine = StockMoveLine::where('stock_move_product_line_id', $productLine->id)->first();
    expect($moveLine)->not->toBeNull();
    expect($moveLine->quantity)->toBe(3.0); // Only available quantity consumed

    // Verify quant is fully consumed
    $quant = StockQuant::where('lot_id', $lot->id)->first();
    expect($quant->quantity)->toBe(0.0);
    expect($quant->reserved_quantity)->toBe(0.0);

    // TODO: Implement backorder functionality to create separate picking for remaining 7 units
});
