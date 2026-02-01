<?php

namespace Kezi\Purchase\Tests\Feature\Filament;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Kezi\Accounting\Models\Tax;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\PurchaseOrderLine;
use Kezi\Purchase\Models\VendorBill;
use Tests\TestCase;

class PurchaseOrderCreateBillActionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected Currency $currency;

    protected Partner $vendor;

    protected Tax $tax;

    protected Product $product;

    protected PurchaseOrder $purchaseOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company);

        $this->currency = Currency::factory()->create(['code' => 'USD']);
        $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);
        $this->tax = Tax::factory()->create(['company_id' => $this->company->id]);
        $this->product = Product::factory()->create(['company_id' => $this->company->id]);

        // Create a purchase order with FullyReceived status (allows bill creation)
        $this->purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->currency->id,
            'status' => PurchaseOrderStatus::FullyReceived,
            'created_by_user_id' => $this->user->id,
        ]);

        // Add a line item to the purchase order
        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $this->purchaseOrder->id,
            'product_id' => $this->product->id,
            'description' => 'Test Product',
            'quantity' => 10,
            'unit_price' => Money::of(100, 'USD'),
            'tax_id' => $this->tax->id,
        ]);

        // Set up Filament tenant context
        Filament::setTenant($this->company);
        $this->actingAs($this->user);
    }

    /** @test */
    public function create_bill_action_is_visible_when_purchase_order_status_allows_bill_creation(): void
    {
        $component = Livewire::test(EditPurchaseOrder::class, ['record' => $this->purchaseOrder->id]);

        // Check that the Create Bill action is present in the header actions
        $component->assertActionExists('create_bill');
    }

    /** @test */
    public function create_bill_action_is_not_visible_when_purchase_order_status_does_not_allow_bill_creation(): void
    {
        // Update PO status to one that doesn't allow bill creation
        $this->purchaseOrder->update(['status' => PurchaseOrderStatus::Draft]);

        $component = Livewire::test(EditPurchaseOrder::class, ['record' => $this->purchaseOrder->id]);

        // Check that the Create Bill action is not present
        $component->assertActionDoesNotExist('create_bill');
    }

    /** @test */
    public function create_bill_action_successfully_creates_vendor_bill_with_all_data(): void
    {
        // Ensure no vendor bills exist initially
        $this->assertDatabaseCount('vendor_bills', 0);

        $component = Livewire::test(EditPurchaseOrder::class, ['record' => $this->purchaseOrder->id]);

        // Execute the create bill action
        $component->callAction('create_bill');

        // Verify vendor bill was created
        $this->assertDatabaseCount('vendor_bills', 1);

        $vendorBill = VendorBill::first();

        // Verify vendor bill data is correctly populated from purchase order
        $this->assertEquals($this->purchaseOrder->vendor_id, $vendorBill->vendor_id);
        $this->assertEquals($this->purchaseOrder->currency_id, $vendorBill->currency_id);
        $this->assertEquals($this->purchaseOrder->id, $vendorBill->purchase_order_id);
        $this->assertEquals($this->user->id, $vendorBill->created_by_user_id);
        $this->assertEquals(now()->format('Y-m-d'), $vendorBill->bill_date->format('Y-m-d'));
        $this->assertEquals(now()->format('Y-m-d'), $vendorBill->accounting_date->format('Y-m-d'));
        $this->assertNotEmpty($vendorBill->bill_reference);

        // Verify vendor bill lines are created
        $this->assertDatabaseCount('vendor_bill_lines', 1);

        $vendorBillLine = $vendorBill->lines->first();
        $purchaseOrderLine = $this->purchaseOrder->lines->first();

        $this->assertEquals($purchaseOrderLine->product_id, $vendorBillLine->product_id);
        $this->assertEquals($purchaseOrderLine->description, $vendorBillLine->description);
        $this->assertEquals($purchaseOrderLine->quantity, $vendorBillLine->quantity);
        $this->assertEquals($purchaseOrderLine->unit_price->getAmount(), $vendorBillLine->unit_price->getAmount());
        $this->assertEquals($purchaseOrderLine->tax_id, $vendorBillLine->tax_id);
    }

    /** @test */
    public function create_bill_action_shows_success_notification(): void
    {
        $component = Livewire::test(EditPurchaseOrder::class, ['record' => $this->purchaseOrder->id]);

        // Execute the create bill action
        $component->callAction('create_bill');

        // Verify success notification is shown
        $component->assertNotified();
    }
}
