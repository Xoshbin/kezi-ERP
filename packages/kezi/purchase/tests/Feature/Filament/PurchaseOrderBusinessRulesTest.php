<?php

namespace Kezi\Purchase\Tests\Feature\Filament;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\VendorBill;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseOrderBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected Partner $vendor;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company);
        $this->actingAs($this->user);

        $this->vendor = Partner::factory()->vendor()->create([
            'company_id' => $this->company->id,
        ]);

        $this->currency = Currency::factory()->createSafely([
            'code' => 'USD',
            'name' => 'US Dollar',
        ]);
    }

    /** @test */
    public function create_bill_action_is_visible_when_no_bills_exist_and_status_allows()
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->currency->id,
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        Livewire::test(EditPurchaseOrder::class, [
            'record' => $purchaseOrder->getRouteKey(),
        ])
            ->assertActionVisible('create_bill');
    }

    /** @test */
    public function create_bill_action_is_hidden_when_bills_already_exist()
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->currency->id,
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        // Create a vendor bill for this purchase order
        VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->currency->id,
            'purchase_order_id' => $purchaseOrder->id,
        ]);

        Livewire::test(EditPurchaseOrder::class, [
            'record' => $purchaseOrder->getRouteKey(),
        ])
            ->assertActionHidden('create_bill');
    }

    /** @test */
    public function create_bill_action_is_hidden_when_status_does_not_allow()
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->currency->id,
            'status' => PurchaseOrderStatus::Draft, // Draft status cannot create bills
        ]);

        Livewire::test(EditPurchaseOrder::class, [
            'record' => $purchaseOrder->getRouteKey(),
        ])
            ->assertActionHidden('create_bill');
    }

    /** @test */
    public function status_field_shows_only_forward_transitions()
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->currency->id,
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        $component = Livewire::test(EditPurchaseOrder::class, [
            'record' => $purchaseOrder->getRouteKey(),
        ]);

        // Get the status field options
        $statusField = $component->instance()->getForm()->getComponent('status');
        $options = $statusField->getOptions();

        // Should include current status and forward transitions
        $this->assertArrayHasKey('confirmed', $options);
        $this->assertArrayHasKey('to_receive', $options);
        $this->assertArrayHasKey('partially_received', $options);
        $this->assertArrayHasKey('fully_received', $options);
        $this->assertArrayHasKey('cancelled', $options); // Special case

        // Should NOT include backward transitions
        $this->assertArrayNotHasKey('draft', $options);
        $this->assertArrayNotHasKey('sent', $options);
    }

    /** @test */
    public function helper_text_shows_bills_already_exist_message()
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->currency->id,
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        // Create a vendor bill for this purchase order
        VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->currency->id,
            'purchase_order_id' => $purchaseOrder->id,
        ]);

        $component = Livewire::test(EditPurchaseOrder::class, [
            'record' => $purchaseOrder->getRouteKey(),
        ]);

        $statusField = $component->instance()->getForm()->getComponent('status');
        $helperText = $statusField->getHelperText();

        $this->assertStringContainsString('Vendor bills already exist', $helperText);
        $this->assertStringContainsString('Status can only be changed forward', $helperText);
    }

    /** @test */
    public function helper_text_shows_can_create_bill_message()
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->currency->id,
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        $component = Livewire::test(EditPurchaseOrder::class, [
            'record' => $purchaseOrder->getRouteKey(),
        ]);

        $statusField = $component->instance()->getForm()->getComponent('status');
        $helperText = $statusField->getHelperText();

        $this->assertStringContainsString('Vendor bills can be created', $helperText);
        $this->assertStringContainsString('Status can only be changed forward', $helperText);
    }

    /** @test */
    public function purchase_order_status_transition_validation()
    {
        // Test forward transitions are allowed
        $this->assertTrue(PurchaseOrderStatus::Draft->canTransitionTo(PurchaseOrderStatus::Sent));
        $this->assertTrue(PurchaseOrderStatus::Sent->canTransitionTo(PurchaseOrderStatus::Confirmed));
        $this->assertTrue(PurchaseOrderStatus::Confirmed->canTransitionTo(PurchaseOrderStatus::ToReceive));

        // Test backward transitions are not allowed
        $this->assertFalse(PurchaseOrderStatus::Sent->canTransitionTo(PurchaseOrderStatus::Draft));
        $this->assertFalse(PurchaseOrderStatus::Confirmed->canTransitionTo(PurchaseOrderStatus::Sent));
        $this->assertFalse(PurchaseOrderStatus::ToReceive->canTransitionTo(PurchaseOrderStatus::Confirmed));

        // Test same status is allowed
        $this->assertTrue(PurchaseOrderStatus::Confirmed->canTransitionTo(PurchaseOrderStatus::Confirmed));

        // Test cancelled can be reached from active statuses
        $this->assertTrue(PurchaseOrderStatus::Confirmed->canTransitionTo(PurchaseOrderStatus::Cancelled));
        $this->assertTrue(PurchaseOrderStatus::ToReceive->canTransitionTo(PurchaseOrderStatus::Cancelled));

        // Test final statuses cannot transition
        $this->assertFalse(PurchaseOrderStatus::Done->canTransitionTo(PurchaseOrderStatus::Confirmed));
        $this->assertFalse(PurchaseOrderStatus::Cancelled->canTransitionTo(PurchaseOrderStatus::Confirmed));
    }

    /** @test */
    public function purchase_order_can_create_bill_logic()
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->currency->id,
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        // Should be able to create bill when status allows and no bills exist
        $this->assertTrue($purchaseOrder->canCreateBill());

        // Create a vendor bill
        VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->currency->id,
            'purchase_order_id' => $purchaseOrder->id,
        ]);

        // Refresh the model to get updated relationships
        $purchaseOrder->refresh();

        // Should not be able to create bill when bills already exist
        $this->assertFalse($purchaseOrder->canCreateBill());
        $this->assertTrue($purchaseOrder->hasBills());
        $this->assertEquals(1, $purchaseOrder->getBillsCount());
    }
}
