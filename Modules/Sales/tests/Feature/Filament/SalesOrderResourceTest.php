<?php

use Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\SalesOrderResource;
use Modules\Sales\Models\SalesOrder;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

test('can render sales order list page', function () {
    /** @var \Tests\TestCase $this */
    $this->get(SalesOrderResource::getUrl('index'))
        ->assertSuccessful();
});

test('can list sales orders', function () {
    /** @var \Tests\TestCase $this */
    $salesOrders = SalesOrder::factory()->count(10)->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    livewire(\Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\ListSalesOrders::class)
        ->assertCanSeeTableRecords($salesOrders);
});

test('can render create sales order page', function () {
    /** @var \Tests\TestCase $this */
    $this->get(SalesOrderResource::getUrl('create'))
        ->assertSuccessful();
});

test('can render edit sales order page', function () {
    /** @var \Tests\TestCase $this */
    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    $this->get(SalesOrderResource::getUrl('edit', ['record' => $salesOrder]))
        ->assertSuccessful();
});
