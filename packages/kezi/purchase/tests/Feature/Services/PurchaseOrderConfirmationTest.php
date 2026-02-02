<?php

namespace Kezi\Purchase\Tests\Feature\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Kezi\Inventory\Enums\Inventory\InventoryAccountingMode;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Enums\Inventory\StockPickingType;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockPicking;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Kezi\Purchase\Events\PurchaseOrderConfirmed;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\PurchaseOrderLine;
use Kezi\Purchase\Services\PurchaseOrderService;
use Tests\TestCase;

class PurchaseOrderConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Product $product;

    protected PurchaseOrderService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->user->companies()->attach($this->company);

        // Set to MANUAL mode since these tests verify stock picking/move creation
        // which only happens in MANUAL_INVENTORY_RECORDING mode
        $this->company->update([
            'inventory_accounting_mode' => InventoryAccountingMode::MANUAL_INVENTORY_RECORDING,
        ]);

        $this->actingAs($this->user);

        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => ProductType::Storable,
            'name' => 'Test Product',
        ]);

        StockLocation::factory()->create([
            'company_id' => $this->company->id,
            'type' => StockLocationType::Internal,
            'name' => 'Main Warehouse',
        ]);

        $this->service = app(PurchaseOrderService::class);
    }

    public function test_confirming_purchase_order_creates_stock_picking_and_moves()
    {
        // Arrange
        $po = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => PurchaseOrderStatus::Draft,
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 100,
            'subtotal' => 1000,
            'total_line_tax' => 0,
            'total' => 1000,
        ]);

        // Act
        $this->service->confirm($po, $this->user);

        // Assert
        $this->assertEquals(PurchaseOrderStatus::ToReceive, $po->refresh()->status);

        // Assert Picking Created
        $this->assertDatabaseHas('stock_pickings', [
            'origin' => $po->po_number,
            'type' => StockPickingType::Receipt->value,
            'company_id' => $this->company->id,
            'partner_id' => $po->vendor_id,
        ]);

        $picking = StockPicking::where('origin', $po->po_number)->first();
        $this->assertNotNull($picking);

        // Assert Stock Moves Created
        $this->assertDatabaseHas('stock_moves', [
            'picking_id' => $picking->id,
            'company_id' => $this->company->id,
            'description' => 'Receive '.$po->po_number,
        ]);

        // Assert Move Lines
        $move = StockMove::where('picking_id', $picking->id)->first();
        $this->assertNotNull($move);

        // Check if product lines relation works (based on manual tinkering earlier)
        $this->assertTrue($move->productLines()->where('product_id', $this->product->id)->exists());
        $this->assertEquals(10, $move->productLines()->sum('quantity'));
    }

    public function test_purchase_order_confirmation_dispatches_event()
    {
        Event::fake([
            PurchaseOrderConfirmed::class,
        ]);

        $po = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => PurchaseOrderStatus::Draft,
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'unit_price' => 100,
            'subtotal' => 500,
            'total_line_tax' => 0,
            'total' => 500,
        ]);

        $this->service->confirm($po, $this->user);

        Event::assertDispatched(PurchaseOrderConfirmed::class, function ($event) use ($po) {
            return $event->purchaseOrder->id === $po->id;
        });
    }
}
