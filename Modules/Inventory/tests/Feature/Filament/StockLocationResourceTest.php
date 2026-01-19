<?php

namespace Modules\Inventory\Tests\Feature\Filament;

use App\Models\Company;
use App\Models\User;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockLocations\Pages\CreateStockLocation;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockLocations\Pages\EditStockLocation;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockLocations\Pages\ListStockLocations;
use Modules\Inventory\Models\StockLocation;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->actingAs($this->user);

    // Set the current tenant context for Filament
    \Filament\Facades\Filament::setTenant($this->company);
});

it('can render stock location list', function () {
    StockLocation::factory()->count(5)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListStockLocations::class)
        ->assertCanSeeTableRecords(StockLocation::all());
});

it('can create stock location', function () {
    $newData = StockLocation::factory()->make([
        'company_id' => $this->company->id,
    ]);

    livewire(CreateStockLocation::class)
        ->fillForm([
            'name' => $newData->name,
            'type' => $newData->type->value,
            'parent_id' => null,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('stock_locations', [
        'name' => $newData->name,
        'type' => $newData->type->value,
        'company_id' => $this->company->id,
    ]);
});

it('can edit stock location', function () {
    $location = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Old Name',
    ]);

    livewire(EditStockLocation::class, [
        'record' => $location->getRouteKey(),
    ])
        ->fillForm([
            'name' => 'New Name',
        ])
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('stock_locations', [
        'id' => $location->id,
        'name' => 'New Name',
    ]);
});

it('can delete stock location', function () {
    $location = StockLocation::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListStockLocations::class)
        ->callTableAction('delete', $location);

    $this->assertDatabaseMissing('stock_locations', [
        'id' => $location->id,
    ]);
});
