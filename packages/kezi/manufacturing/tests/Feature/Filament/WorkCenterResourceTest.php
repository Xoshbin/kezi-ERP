<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\WorkCenterResource;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\WorkCenterResource\Pages\CreateWorkCenter;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\WorkCenterResource\Pages\EditWorkCenter;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\WorkCenterResource\Pages\ListWorkCenters;
use Kezi\Manufacturing\Models\WorkCenter;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

describe('WorkCenterResource', function () {
    it('can render the list page', function () {
        $this->get(WorkCenterResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('can render the create page', function () {
        $this->get(WorkCenterResource::getUrl('create'))
            ->assertSuccessful();
    });

    it('can render the edit page', function () {
        $wc = WorkCenter::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->get(WorkCenterResource::getUrl('edit', ['record' => $wc]))
            ->assertSuccessful();
    });

    it('can list work centers', function () {
        $wc = WorkCenter::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Main Assembly Line'],
        ]);

        Livewire::test(ListWorkCenters::class)
            ->assertCanSeeTableRecords([$wc])
            ->assertSee('Main Assembly Line');
    });

    it('can create a work center', function () {
        Livewire::test(CreateWorkCenter::class)
            ->fillForm([
                'code' => 'WC-NEW-001',
                'name' => 'New Work Center',
                'hourly_cost' => 50.0,
                'capacity' => 10,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('work_centers', [
            'company_id' => $this->company->id,
            'code' => 'WC-NEW-001',
        ]);
    });

    it('can update a work center', function () {
        $wc = WorkCenter::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Old Name'],
        ]);

        Livewire::test(EditWorkCenter::class, ['record' => $wc->getRouteKey()])
            ->fillForm([
                'name' => 'Updated Name',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($wc->fresh()->name)->toBe('Updated Name');
    });

    it('can delete a work center', function () {
        $wc = WorkCenter::factory()->create([
            'company_id' => $this->company->id,
        ]);

        Livewire::test(EditWorkCenter::class, ['record' => $wc->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('work_centers', [
            'id' => $wc->id,
        ]);
    });
});
