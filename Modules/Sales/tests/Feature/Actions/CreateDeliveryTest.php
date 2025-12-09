<?php

namespace Modules\Sales\Tests\Feature\Actions;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Enums\Inventory\StockLocationType;
use Modules\Inventory\Models\StockLocation;
use Modules\Product\Models\Product;
use Modules\Sales\Actions\Sales\CreateDeliveryFromSalesOrderAction;
use Modules\Sales\DataTransferObjects\Sales\CreateDeliveryFromSalesOrderDTO;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;
use Tests\TestCase;

class CreateDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Product $product;

    protected StockLocation $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();

        $this->warehouse = StockLocation::factory()->create([
            'company_id' => $this->company->id,
            'type' => StockLocationType::Internal,
        ]);

        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        ]);

        $journal = \Modules\Accounting\Models\Journal::factory()->create([
            'company_id' => $this->company->id,
            'type' => \Modules\Accounting\Enums\Accounting\JournalType::Sale,
        ]);
        $this->company->update(['default_sales_journal_id' => $journal->id]);
    }

    public function test_create_delivery_from_sales_order_success()
    {
        $salesOrder = SalesOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => \Modules\Sales\Enums\Sales\SalesOrderStatus::Confirmed,
            'delivery_location_id' => $this->warehouse->id,
        ]);

        SalesOrderLine::factory()->create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'quantity_delivered' => 0,
            'unit_price' => Money::of(100, 'USD'),
            'subtotal' => Money::of(1000, 'USD'),
            'total_line_tax' => Money::of(0, 'USD'),
            'total' => Money::of(1000, 'USD'),
        ]);

        $dto = new CreateDeliveryFromSalesOrderDTO(
            salesOrder: $salesOrder,
            user: $this->user,
            autoConfirm: true,
        );

        $action = app(CreateDeliveryFromSalesOrderAction::class);
        $stockMoves = $action->execute($dto);

        $this->assertCount(1, $stockMoves);
        $this->assertEquals(10, $stockMoves->first()->productLines->first()->quantity);
    }
}
