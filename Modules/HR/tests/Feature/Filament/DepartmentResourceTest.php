<?php

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Departments\DepartmentResource;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Departments\Pages\CreateDepartment;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Departments\Pages\EditDepartment;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Departments\Pages\ListDepartments;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

describe('DepartmentResource', function () {
    it('can render list page', function () {
        $this->actingAs($this->user)
            ->get(DepartmentResource::getUrl('index', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can render create page', function () {
        $this->actingAs($this->user)
            ->get(DepartmentResource::getUrl('create', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can render edit page', function () {
        $department = Department::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(DepartmentResource::getUrl('edit', ['record' => $department], tenant: $this->company))
            ->assertSuccessful();
    });

    it('can list departments', function () {
        $departments = Department::factory()->count(3)->create(['company_id' => $this->company->id]);

        livewire(ListDepartments::class)
            ->assertCanSeeTableRecords($departments);
    });

    it('can create department', function () {
        $manager = Employee::factory()->create(['company_id' => $this->company->id]);
        $parent = Department::factory()->create(['company_id' => $this->company->id]);

        $livewire = livewire(CreateDepartment::class, ['tenant' => $this->company->id])
            ->fillForm([
                'name' => 'Engineering',
                'parent_department_id' => $parent->id,
                'description' => 'Engineering Department',
                'manager_id' => $manager->id,
                'is_active' => true,
            ])
            ->call('create');

        if ($livewire->errors()->isNotEmpty()) {
            dd($livewire->errors()->toArray());
        }

        $livewire->assertHasNoFormErrors();

        $this->assertDatabaseHas('departments', [
            'company_id' => $this->company->id,
            'parent_department_id' => $parent->id,
            'manager_id' => $manager->id,
            'is_active' => true,
        ]);
    });

    it('validates required fields', function () {
        livewire(CreateDepartment::class)
            ->fillForm([
                'name' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
            ]);
    });

    it('can edit department', function () {
        $department = Department::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Original Name',
        ]);

        livewire(EditDepartment::class, ['record' => $department->getRouteKey()])
            ->fillForm([
                'name' => 'Updated Name',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($department->refresh()->name)->toBe('Updated Name');
    });

    /*
    it('can delete department', function () {
        $department = Department::factory()->create(['company_id' => $this->company->id]);

        livewire(ListDepartments::class)
            ->callTableAction('delete', $department);

        $this->assertDatabaseMissing('departments', [
            'id' => $department->id,
        ]);
    });
    */
});
