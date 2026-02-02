<?php

namespace Kezi\Purchase\Tests\Feature\Services;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Enums\Incoterm;
use Kezi\Foundation\Enums\ShippingCostType;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Models\VendorBillLine;
use Kezi\Purchase\Services\ShippingCostAllocationService;
use Tests\TestCase;

class ShippingCostAllocationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ShippingCostAllocationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShippingCostAllocationService::class);
    }

    public function test_it_identifies_shipping_lines_by_type()
    {
        $bill = VendorBill::factory()->create();

        $line1 = VendorBillLine::factory()->create([
            'vendor_bill_id' => $bill->id,
            'shipping_cost_type' => ShippingCostType::Freight,
        ]);

        $line2 = VendorBillLine::factory()->create([
            'vendor_bill_id' => $bill->id,
            'shipping_cost_type' => null,
        ]);

        $shippingLines = $this->service->getShippingLines($bill);

        $this->assertCount(1, $shippingLines);
        $this->assertEquals($line1->id, $shippingLines->first()->id);
    }

    public function test_it_validates_ddp_vendor_bill_with_no_shipping_costs()
    {
        $bill = VendorBill::factory()->create(['incoterm' => Incoterm::Ddp]);

        VendorBillLine::factory()->create([
            'vendor_bill_id' => $bill->id,
            'description' => 'Product A',
            'shipping_cost_type' => null,
        ]);

        $result = $this->service->validateVendorBillShippingCosts($bill);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->warnings);
    }

    public function test_it_warns_when_buyer_pays_freight_on_ddp()
    {
        // DDP means seller pays everything. If buyer pays freight, it's a warning.
        $bill = VendorBill::factory()->create([
            'incoterm' => Incoterm::Ddp,
            'currency_id' => \Kezi\Foundation\Models\Currency::factory()->createSafely(['code' => 'USD'])->id,
        ]);

        VendorBillLine::factory()->create([
            'vendor_bill_id' => $bill->id,
            'description' => 'Freight Charges',
            'shipping_cost_type' => ShippingCostType::Freight,
            'unit_price' => Money::of(100, 'USD'),
            'quantity' => 1,
            'subtotal' => Money::of(100, 'USD'),
            'total_line_tax' => Money::of(0, 'USD'),
        ]);

        $result = $this->service->validateVendorBillShippingCosts($bill);

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->warnings);
        $this->assertStringContainsString('seller typically pays for these costs', $result->warnings[0]);
    }

    public function test_it_allows_freight_on_exw()
    {
        // EXW means buyer pays everything. Freight is allowed.
        $bill = VendorBill::factory()->create([
            'incoterm' => Incoterm::Exw,
            'currency_id' => \Kezi\Foundation\Models\Currency::factory()->createSafely(['code' => 'USD'])->id,
        ]);

        VendorBillLine::factory()->create([
            'vendor_bill_id' => $bill->id,
            'description' => 'Freight Charges',
            'shipping_cost_type' => ShippingCostType::Freight,
            'unit_price' => Money::of(100, 'USD'),
            'quantity' => 1,
            'subtotal' => Money::of(100, 'USD'),
            'total_line_tax' => Money::of(0, 'USD'),
        ]);

        $result = $this->service->validateVendorBillShippingCosts($bill);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->warnings);
    }
}
