<?php

namespace Modules\Inventory\Tests\Feature\Filament;

use App\Models\Company;
use App\Models\User;
use Modules\Inventory\Enums\Inventory\LandedCostStatus;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\LandedCostResource\Pages\CreateLandedCost;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\LandedCostResource\Pages\EditLandedCost;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\LandedCostResource\Pages\ListLandedCosts;
use Modules\Inventory\Models\LandedCost;
use Modules\Purchase\Models\VendorBill;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $currency = \Modules\Foundation\Models\Currency::factory()->create([
        'code' => 'USD',
        'decimal_places' => 2,
    ]);

    $this->company = Company::factory()->create([
        'currency_id' => $currency->id,
    ]);
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->actingAs($this->user);

    // Set the current tenant context for Filament
    \Filament\Facades\Filament::setTenant($this->company);
});

it('can render landed cost list', function () {
    LandedCost::factory()->count(5)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListLandedCosts::class)
        ->assertCanSeeTableRecords(LandedCost::all());
});

it('can create landed cost', function () {
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $newData = LandedCost::factory()->make([
        'company_id' => $this->company->id,
        'vendor_bill_id' => $vendorBill->id,
    ]);

    livewire(CreateLandedCost::class)
        ->fillForm([
            'vendor_bill_id' => $vendorBill->id,
            'date' => $newData->date->format('Y-m-d'),
            'amount_total' => 1000,
            'allocation_method' => $newData->allocation_method->value,
            'description' => $newData->description,
            'status' => LandedCostStatus::Draft->value,
        ])
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('landed_costs', [
        'company_id' => $this->company->id,
        'vendor_bill_id' => $vendorBill->id,
        'amount_total' => 100000, // Money cast * 100 assuming base currency handling
        'description' => $newData->description,
        'status' => LandedCostStatus::Draft->value,
    ]);
});

it('can edit landed cost', function () {
    $landedCost = LandedCost::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(EditLandedCost::class, [
        'record' => $landedCost->getRouteKey(),
    ])
        ->fillForm([
            'description' => 'Updated Description',
            'amount_total' => 500,
        ])
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('landed_costs', [
        'id' => $landedCost->id,
        'description' => 'Updated Description',
        'amount_total' => 50000, // Money cast
    ]);
});

it('can bulk delete landed costs', function () {
    $landedCosts = LandedCost::factory()->count(3)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListLandedCosts::class)
        ->callTableBulkAction('delete', $landedCosts);

    foreach ($landedCosts as $landedCost) {
        $this->assertDatabaseMissing('landed_costs', [
            'id' => $landedCost->id,
        ]);
    }
});
