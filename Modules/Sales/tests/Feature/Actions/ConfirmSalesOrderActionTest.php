<?php

namespace Modules\Sales\Tests\Feature\Actions;

use App\Models\Company;
use App\Models\User;
use Modules\Foundation\Services\SequenceService;
use Modules\Inventory\Enums\Inventory\InventoryAccountingMode;
use Modules\Sales\Actions\Sales\ConfirmSalesOrderAction;
use Modules\Sales\Actions\Sales\CreateDeliveryFromSalesOrderAction;
use Modules\Sales\Enums\Sales\SalesOrderStatus;
use Modules\Sales\Models\SalesOrder;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);

    $this->company->update([
        'inventory_accounting_mode' => InventoryAccountingMode::AUTO_RECORD_ON_BILL,
    ]);
});

it('confirm sales order success', function () {
    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => SalesOrderStatus::Draft,
        'expected_delivery_date' => now()->addDays(5)->toDateTimeString(),
    ]);

    // Mock SequenceService to return a predicted number
    $this->mock(SequenceService::class, function ($mock) {
        $mock->shouldReceive('getNextNumber')
            ->once()
            ->andReturn('SO-00001');
    });

    // Mock CreateDeliveryFromSalesOrderAction to avoid full inventory logic dependency in this unit test
    $this->mock(CreateDeliveryFromSalesOrderAction::class, function ($mock) {
        $mock->shouldReceive('execute')->once();
    });

    $action = app(ConfirmSalesOrderAction::class);
    $confirmedOrder = $action->execute($salesOrder, $this->user);

    expect($confirmedOrder->status)->toBe(SalesOrderStatus::Confirmed)
        ->and($confirmedOrder->so_number)->toBe('SO-00001')
        ->and($confirmedOrder->confirmed_at)->not->toBeNull();
});

it('cannot confirm non draft order', function () {
    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => SalesOrderStatus::Cancelled,
    ]);

    $action = app(ConfirmSalesOrderAction::class);
    $result = $action->execute($salesOrder, $this->user);

    expect($result->status)->toBe(SalesOrderStatus::Cancelled)
        ->and($result->confirmed_at)->toBeNull();
});
