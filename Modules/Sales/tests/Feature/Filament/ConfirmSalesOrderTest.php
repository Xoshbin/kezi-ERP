<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Foundation\Models\Partner;
use Modules\Sales\Enums\Sales\SalesOrderStatus;
use Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\EditSalesOrder;
use Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\ViewSalesOrder;
use Modules\Sales\Models\SalesOrder;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

it('has confirm action on view page', function () {
    /** @var Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var SalesOrder $salesOrder */
    $salesOrder = SalesOrder::create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'so_date' => now(),
        'so_number' => 'SO-TEST-001',
        'status' => SalesOrderStatus::Draft,
        'total_amount' => Money::of(0, $this->company->currency->code),
        'created_by_user_id' => $this->user->id,
    ]);

    livewire(ViewSalesOrder::class, [
        'record' => $salesOrder->getRouteKey(),
    ])
        ->assertActionExists('confirm')
        ->callAction('confirm')
        ->assertNotified(); // Check for notification instead of redirect if we standardized it

    expect($salesOrder->refresh()->status)->toBe(SalesOrderStatus::Confirmed);
});

it('confirms sales order from edit page without redirecting', function () {
    /** @var Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var SalesOrder $salesOrder */
    $salesOrder = SalesOrder::create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'so_date' => now(),
        'so_number' => 'SO-TEST-002',
        'status' => SalesOrderStatus::Draft,
        'total_amount' => Money::of(0, $this->company->currency->code),
        'created_by_user_id' => $this->user->id,
    ]);

    livewire(EditSalesOrder::class, [
        'record' => $salesOrder->getRouteKey(),
    ])
        ->assertActionExists('confirm')
        ->callAction('confirm')
        ->assertNotified() // Should notify success
        ->assertStatus(200); // Should stay on page (no redirect exception)

    expect($salesOrder->refresh()->status)->toBe(SalesOrderStatus::Confirmed);
});
