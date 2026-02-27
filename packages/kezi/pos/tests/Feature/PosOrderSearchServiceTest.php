<?php

namespace Kezi\Pos\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Pos\Enums\PosReturnStatus;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosOrderLine;
use Kezi\Pos\Models\PosReturn;
use Kezi\Pos\Models\PosReturnLine;
use Kezi\Pos\Services\PosOrderSearchService;
use Tests\TestCase;

class PosOrderSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PosOrderSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PosOrderSearchService::class);
        $this->currency = \Kezi\Foundation\Models\Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true]
        );
    }

    public function test_order_is_eligible_for_return_if_partially_returned()
    {
        $order = PosOrder::factory()->create([
            'status' => \Kezi\Pos\Enums\PosOrderStatus::Paid,
            'currency_id' => $this->currency->id,
            'company_id' => \App\Models\Company::factory()->create(['currency_id' => $this->currency->id])->id,
        ]);
        $line1 = PosOrderLine::factory()->create([
            'pos_order_id' => $order->id,
            'quantity' => 2,
        ]);
        $line2 = PosOrderLine::factory()->create([
            'pos_order_id' => $order->id,
            'quantity' => 1,
        ]);

        // Create a partial return for line 1
        $return = PosReturn::factory()->create([
            'original_order_id' => $order->id,
            'status' => PosReturnStatus::Completed,
            'currency_id' => $this->currency->id,
            'company_id' => $order->company_id,
        ]);
        PosReturnLine::factory()->create([
            'pos_return_id' => $return->id,
            'original_order_line_id' => $line1->id,
            'quantity_returned' => 1,
        ]);

        $result = $this->service->isEligibleForReturn($order, []);

        $this->assertTrue($result['eligible']);
        $this->assertEmpty($result['reasons']);
    }

    public function test_order_is_not_eligible_if_fully_returned()
    {
        $order = PosOrder::factory()->create([
            'status' => \Kezi\Pos\Enums\PosOrderStatus::Paid,
            'currency_id' => $this->currency->id,
            'company_id' => \App\Models\Company::factory()->create(['currency_id' => $this->currency->id])->id,
        ]);
        $line1 = PosOrderLine::factory()->create([
            'pos_order_id' => $order->id,
            'quantity' => 1,
        ]);

        // Create a full return
        $return = PosReturn::factory()->create([
            'original_order_id' => $order->id,
            'status' => PosReturnStatus::Completed,
            'currency_id' => $this->currency->id,
            'company_id' => $order->company_id,
        ]);
        PosReturnLine::factory()->create([
            'pos_return_id' => $return->id,
            'original_order_line_id' => $line1->id,
            'quantity_returned' => 1,
        ]);

        $result = $this->service->isEligibleForReturn($order, []);

        $this->assertFalse($result['eligible']);
        $this->assertContains('Order has been fully returned', $result['reasons']);
    }

    public function test_order_is_eligible_if_previous_return_was_cancelled()
    {
        $order = PosOrder::factory()->create([
            'status' => \Kezi\Pos\Enums\PosOrderStatus::Paid,
            'currency_id' => $this->currency->id,
            'company_id' => \App\Models\Company::factory()->create(['currency_id' => $this->currency->id])->id,
        ]);
        $line = PosOrderLine::factory()->create([
            'pos_order_id' => $order->id,
            'quantity' => 1,
        ]);

        // Create a cancelled return
        $return = PosReturn::factory()->create([
            'original_order_id' => $order->id,
            'status' => PosReturnStatus::Cancelled,
            'currency_id' => $this->currency->id,
            'company_id' => $order->company_id,
        ]);
        PosReturnLine::factory()->create([
            'pos_return_id' => $return->id,
            'original_order_line_id' => $line->id,
            'quantity_returned' => 1,
        ]);

        $result = $this->service->isEligibleForReturn($order, []);

        $this->assertTrue($result['eligible']);
    }
}
