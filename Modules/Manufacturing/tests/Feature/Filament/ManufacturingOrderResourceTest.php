<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Inventory\Models\StockLocation;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource;
use Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource\Pages\CreateManufacturingOrder;
use Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource\Pages\ListManufacturingOrders;
use Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource\Pages\ViewManufacturingOrder;
use Modules\Manufacturing\Models\BillOfMaterial;
use Modules\Manufacturing\Models\ManufacturingOrder;
use Modules\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);

    $this->sourceLocation = StockLocation::factory()->create(['company_id' => $this->company->id]);
    $this->destLocation = StockLocation::factory()->create(['company_id' => $this->company->id]);
});

describe('ManufacturingOrderResource', function () {
    it('can render the list page', function () {
        $this->get(ManufacturingOrderResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('can render the create page', function () {
        $this->get(ManufacturingOrderResource::getUrl('create'))
            ->assertSuccessful();
    });

    it('can render the view page', function () {
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->get(ManufacturingOrderResource::getUrl('view', ['record' => $mo]))
            ->assertSuccessful();
    });

    it('can list manufacturing orders', function () {
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'number' => 'MO-TEST-123',
        ]);

        Livewire::test(ListManufacturingOrders::class)
            ->assertCanSeeTableRecords([$mo])
            ->assertSee('MO-TEST-123');
    });

    it('can create a manufacturing order', function () {
        $bom = BillOfMaterial::factory()->create(['company_id' => $this->company->id]);

        Livewire::test(CreateManufacturingOrder::class)
            ->fillForm([
                'bom_id' => $bom->id,
                'product_id' => $bom->product_id,
                'quantity_to_produce' => 5,
                'source_location_id' => $this->sourceLocation->id,
                'destination_location_id' => $this->destLocation->id,
                'planned_start_date' => now()->toDateString(),
                'planned_end_date' => now()->addDays(7)->toDateString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('manufacturing_orders', [
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'quantity_to_produce' => 5,
        ]);
    });

    it('can confirm a manufacturing order from view page', function () {
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::Draft,
        ]);

        Livewire::test(ViewManufacturingOrder::class, ['record' => $mo->getRouteKey()])
            ->callAction('confirm')
            ->assertHasNoActionErrors();

        expect($mo->fresh()->status)->toBe(ManufacturingOrderStatus::Confirmed);
    });

    it('can start production from view page', function () {
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::Confirmed,
        ]);

        Livewire::test(ViewManufacturingOrder::class, ['record' => $mo->getRouteKey()])
            ->callAction('start_production')
            ->assertHasNoActionErrors();

        expect($mo->fresh()->status)->toBe(ManufacturingOrderStatus::InProgress);
    });

    it('can complete production from view page', function () {
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::InProgress,
        ]);

        // Add a line so it doesn't fail
        $mo->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => Product::factory()->create(['company_id' => $this->company->id])->id,
            'quantity_required' => 1,
            'quantity_consumed' => 1,
            'unit_cost' => 1000,
            'currency_code' => $this->company->currency->code,
        ]);

        Livewire::test(ViewManufacturingOrder::class, ['record' => $mo->getRouteKey()])
            ->callAction('complete')
            ->assertHasNoActionErrors();

        expect($mo->fresh()->status)->toBe(ManufacturingOrderStatus::Done);
    });
});
