<?php

namespace Kezi\HR\Tests\Feature\Filament\Forms\Components;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Kezi\HR\Filament\Forms\Components\EmployeeSelectField;
use Kezi\HR\Models\Employee;
use Livewire\Component;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, \Tests\Traits\WithConfiguredCompany::class);

beforeEach(function () {
    /** @var \Tests\TestCase $test */
    $test = $this;
    $test->setupWithConfiguredCompany();

    \Filament\Facades\Filament::setCurrentPanel(
        \Filament\Facades\Filament::getPanel('kezi')
    );
});

it('can extract schema from EmployeeResource', function () {
    $component = EmployeeSelectField::make('employee_id');

    $mockHasForms = new class extends Component implements HasForms
    {
        use InteractsWithForms;

        public function render(): string
        {
            return '<div></div>';
        }
    };

    $schema = Schema::make($mockHasForms);

    $components = $component->getCreateOptionActionForm($schema);

    expect($components)->toBeArray()
        ->and($components)->not->toBeEmpty();
});

it('can create an employee using the component logic', function () {
    /** @var \Tests\TestCase $test */
    $test = $this;
    $component = EmployeeSelectField::make('employee_id');

    /** @var \Closure $createOptionUsing */
    $createOptionUsing = $component->getCreateOptionUsing();
    expect($createOptionUsing)->toBeCallable();

    /** @var \App\Models\Company $company */
    $company = \Filament\Facades\Filament::getTenant();

    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'employee_number' => 'EMP2024001',
        'hire_date' => now()->format('Y-m-d'),
    ];

    $id = (int) $createOptionUsing($data);

    expect($id)->toBeInt();
    $this->assertDatabaseHas('employees', [
        'id' => $id,
        'company_id' => $company->id,
        'email' => 'john.doe@example.com',
    ]);

    /** @var Employee $employee */
    $employee = Employee::find($id);
    expect($employee->first_name)->toBe('John');
});

it('can apply a custom query filter', function () {
    /** @var \Tests\TestCase $test */
    $test = $this;
    /** @var \App\Models\Company $company */
    $company = \Filament\Facades\Filament::getTenant();

    $activeEmployee = Employee::factory()->create([
        'company_id' => $company->id,
        'is_active' => true,
        'employment_status' => 'active',
        'first_name' => 'Active',
    ]);

    $inactiveEmployee = Employee::factory()->create([
        'company_id' => $company->id,
        'is_active' => false,
        'employment_status' => 'terminated',
        'first_name' => 'Inactive',
    ]);

    $component = EmployeeSelectField::make('employee_id')
        ->query(fn ($query) => $query->where('is_active', true)->where('employment_status', 'active'));

    /** @var \Closure $customQueryModifier */
    $customQueryModifier = $component->getCustomQueryModifier();
    expect($customQueryModifier)->toBeCallable();

    $query = Employee::query();
    $customQueryModifier($query);

    $results = $query->get();

    expect($results)->toHaveCount(1);

    /** @var Employee $fistResult */
    $fistResult = $results->first();

    expect($fistResult->id)->toBe($activeEmployee->id)
        ->and($results->pluck('id'))->not->toContain($inactiveEmployee->id);
});
