<?php

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Positions\Pages\CreatePosition;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Positions\Pages\EditPosition;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Positions\Pages\ListPositions;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Positions\PositionResource;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

describe('PositionResource', function () {
    it('can render list page', function () {
        $this->actingAs($this->user)
            ->get(PositionResource::getUrl('index', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can render create page', function () {
        $this->actingAs($this->user)
            ->get(PositionResource::getUrl('create', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can list positions', function () {
        $positions = Position::factory()->count(3)->create(['company_id' => $this->company->id]);

        livewire(ListPositions::class)
            ->assertCanSeeTableRecords($positions);
    });

    it('can create position', function () {
        $department = Department::factory()->create(['company_id' => $this->company->id]);

        livewire(CreatePosition::class, ['tenant' => $this->company->id])
            ->fillForm([
                'title' => 'Software Engineer',
                'department_id' => $department->id,
                'employment_type' => 'full_time',
                'level' => 'mid',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('positions', [
            'company_id' => $this->company->id,
            'department_id' => $department->id,
            'employment_type' => 'full_time',
            'level' => 'mid',
            'is_active' => true,
        ]);
    });

    it('validates required fields', function () {
        livewire(CreatePosition::class, ['tenant' => $this->company->id])
            ->fillForm([
                'title' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'title' => 'required',
            ]);
    });

    it('can edit position', function () {
        $position = Position::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Original Title',
        ]);

        livewire(EditPosition::class, ['record' => $position->getRouteKey()])
            ->fillForm([
                'title' => 'Updated Title',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($position->refresh()->title)->toBe('Updated Title');
    });

    it('can delete position via bulk action', function () {
        $position = Position::factory()->create(['company_id' => $this->company->id]);

        livewire(ListPositions::class)
            ->callTableBulkAction('delete', [$position]);

        $this->assertDatabaseMissing('positions', [
            'id' => $position->id,
        ]);
    });
});
