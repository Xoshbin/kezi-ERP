<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockPickingType;
use Kezi\Inventory\Events\Inventory\StockMoveConfirmed;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockPicking;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Kezi\Sales\Actions\Sales\CreateStockMovesForInvoiceAction;
use Kezi\Sales\DataTransferObjects\Sales\CreateStockMovesForInvoiceDTO;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Models\InvoiceLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CreateStockMovesForInvoiceAction::class);
    Event::fake([StockMoveConfirmed::class]);
});

it('creates stock moves for storable products on invoice', function () {
    // Setup Locations
    $warehouse = StockLocation::factory()->create(['company_id' => $this->company->id, 'type' => \Kezi\Inventory\Enums\Inventory\StockLocationType::Internal, 'name' => 'Warehouse']);
    $customerLoc = StockLocation::factory()->create(['company_id' => $this->company->id, 'type' => \Kezi\Inventory\Enums\Inventory\StockLocationType::Customer, 'name' => 'Customers']);

    // Ensure company has default stock location set if logic relies on it (though Action has fallbacks)
    $this->company->update(['default_stock_location_id' => $warehouse->id]);

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'invoice_number' => 'INV-001',
        'posted_at' => now(),
    ]);

    $storableProduct = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
    ]);

    $serviceProduct = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Service,
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $storableProduct->id,
        'quantity' => 10,
        'description' => 'Storable Item',
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $serviceProduct->id,
        'quantity' => 5,
        'description' => 'Service Item',
    ]);

    $dto = new CreateStockMovesForInvoiceDTO(
        invoice: $invoice,
        user: $this->user,
    );

    $moves = $this->action->execute($dto);

    // Verify Picking
    $picking = StockPicking::where('origin', 'Invoice#'.$invoice->id)->first();
    expect($picking)->not->toBeNull()
        ->and($picking->type)->toBe(StockPickingType::Delivery)
        ->and($picking->reference)->toBe($invoice->invoice_number);

    // Verify Moves
    expect($moves)->toHaveCount(1); // Only for storable product

    $move = $moves->first();
    expect($move->productLines->first()->product_id)->toBe($storableProduct->id)
        ->and((float) $move->productLines->first()->quantity)->toBe(10.0)
        ->and($move->picking_id)->toBe($picking->id)
        ->and($move->status)->toBe(StockMoveStatus::Done);

    // Verify Event
    Event::assertDispatched(StockMoveConfirmed::class, function ($event) use ($move) {
        return $event->stockMove->id === $move->id;
    });
});

it('skips stock move creation if no storable products', function () {
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $serviceProduct = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Service,
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $serviceProduct->id,
        'quantity' => 5,
    ]);

    $dto = new CreateStockMovesForInvoiceDTO(
        invoice: $invoice,
        user: $this->user,
    );

    $moves = $this->action->execute($dto);

    expect($moves)->toBeEmpty();
    expect(StockPicking::where('origin', 'Invoice#'.$invoice->id)->count())->toBe(0);
});
