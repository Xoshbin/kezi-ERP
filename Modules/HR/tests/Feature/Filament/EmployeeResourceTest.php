<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Employees\EmployeeResource;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Employees\Pages\CreateEmployee;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Employees\Pages\EditEmployee;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Employees\Pages\ListEmployees;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Position;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('EmployeeResource', function () {
    it('can render list page', function () {
        $this->actingAs($this->user)
            ->get(EmployeeResource::getUrl())
            ->assertSuccessful();
    });

    it('can render create page', function () {
        $this->actingAs($this->user)
            ->get(EmployeeResource::getUrl('create'))
            ->assertSuccessful();
    });

    it('can render edit page', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user)
            ->get(EmployeeResource::getUrl('edit', ['record' => $employee]))
            ->assertSuccessful();
    });

    it('can list employees', function () {
        $employees = Employee::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        livewire(ListEmployees::class)
            ->assertCanSeeTableRecords($employees);
    });

    it('can create employee', function () {
        $department = Department::factory()->create(['company_id' => $this->company->id]);
        $position = Position::factory()->create(['company_id' => $this->company->id]);

        $newData = Employee::factory()->make([
            'company_id' => $this->company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        livewire(CreateEmployee::class)
            ->fillForm([
                'employee_number' => 'EMP-TEST-001',
                'first_name' => $newData->first_name,
                'last_name' => $newData->last_name,
                'email' => $newData->email,
                'phone' => '1234567890',
                'department_id' => $department->id,
                'position_id' => $position->id,
                'hire_date' => $newData->hire_date,
                'date_of_birth' => $newData->date_of_birth,
                'gender' => $newData->gender,
                'marital_status' => $newData->marital_status,
                'nationality' => $newData->nationality,
                'national_id' => $newData->national_id,
                'address_line_1' => $newData->address_line_1,
                'city' => $newData->city,
                'country' => $newData->country,
                'employment_status' => 'active',
                'employee_type' => 'full_time',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('employees', [
            'first_name' => $newData->first_name,
            'last_name' => $newData->last_name,
            'email' => $newData->email,
        ]);
    });

    it('validates required fields', function () {
        livewire(CreateEmployee::class)
            ->fillForm([
                'first_name' => null,
                'last_name' => null,
                'email' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required',
            ]);
    });

    it('can edit employee', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'emergency_contact_phone' => '1234567890', // Valid phone
            'phone' => '1234567890',
        ]);

        $newFirstName = 'Updated Name';

        livewire(EditEmployee::class, ['record' => $employee->getRouteKey()])
            ->fillForm([
                'first_name' => $newFirstName,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($employee->refresh()->first_name)->toBe($newFirstName);
    });

    it('can delete employee', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        livewire(ListEmployees::class)
            ->callTableAction('delete', $employee);

        $this->assertSoftDeleted($employee);
    });
});
