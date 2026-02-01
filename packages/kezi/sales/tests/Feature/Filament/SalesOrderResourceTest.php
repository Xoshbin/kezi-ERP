<?php

namespace Kezi\Sales\Tests\Feature\Filament;

use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\CreateSalesOrder;
use Kezi\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\ListSalesOrders;
use Kezi\Sales\Models\SalesOrder;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);

    $this->customer = Partner::factory()->customer()->create(['company_id' => $this->company->id]);
    $this->currency = Currency::factory()->create(['code' => 'USD']);

    auth()->login($this->user);
    Filament::setTenant($this->company);
});

it('can render list page', function () {
    SalesOrder::factory()->count(5)->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
    ]);

    Livewire::test(ListSalesOrders::class)
        ->assertCanSeeTableRecords(SalesOrder::take(5)->get())
        ->assertStatus(200);
});

it('can render create page', function () {
    Livewire::test(CreateSalesOrder::class)
        ->assertStatus(200);
});
