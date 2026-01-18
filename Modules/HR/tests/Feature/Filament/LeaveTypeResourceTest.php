<?php

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Filament\Clusters\HumanResources\Resources\LeaveTypes\LeaveTypeResource;
use Modules\HR\Filament\Clusters\HumanResources\Resources\LeaveTypes\Pages\CreateLeaveType;
use Modules\HR\Filament\Clusters\HumanResources\Resources\LeaveTypes\Pages\EditLeaveType;
use Modules\HR\Filament\Clusters\HumanResources\Resources\LeaveTypes\Pages\ListLeaveTypes;
use Modules\HR\Models\LeaveType;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

describe('LeaveTypeResource', function () {
    it('can render list page', function () {
        $this->actingAs($this->user)
            ->get(LeaveTypeResource::getUrl('index', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can render create page', function () {
        $this->actingAs($this->user)
            ->get(LeaveTypeResource::getUrl('create', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can render edit page', function () {
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(LeaveTypeResource::getUrl('edit', ['record' => $leaveType], tenant: $this->company))
            ->assertSuccessful();
    });

    it('can list leave types', function () {
        $leaveTypes = LeaveType::factory()->count(3)->create(['company_id' => $this->company->id]);

        livewire(ListLeaveTypes::class)
            ->assertCanSeeTableRecords($leaveTypes);
    });

    it('can create leave type', function () {
        $code = 'SICK01';
        livewire(CreateLeaveType::class, ['tenant' => $this->company->id])
            ->fillForm([
                'name' => 'Sick Leave',
                'code' => $code,
                'description' => 'Sick Leave Type',
                'default_days_per_year' => 15,
                'is_paid' => true,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leave_types', [
            'company_id' => $this->company->id,
            'code' => $code,
            'default_days_per_year' => 15,
            'is_paid' => true,
            'is_active' => true,
        ]);
    });

    it('validates required fields', function () {
        livewire(CreateLeaveType::class, ['tenant' => $this->company->id])
            ->fillForm([
                'name' => null,
                'code' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'code' => 'required',
            ]);
    });

    it('can edit leave type', function () {
        $leaveType = LeaveType::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Original Name',
        ]);

        livewire(EditLeaveType::class, ['record' => $leaveType->getRouteKey()])
            ->fillForm([
                'name' => 'Updated Name',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($leaveType->refresh()->name)->toBe('Updated Name');
    });

    it('can delete leave type via bulk action', function () {
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);

        livewire(ListLeaveTypes::class)
            ->callTableBulkAction('delete', [$leaveType]);

        $this->assertDatabaseMissing('leave_types', [
            'id' => $leaveType->id,
        ]);
    });
});
