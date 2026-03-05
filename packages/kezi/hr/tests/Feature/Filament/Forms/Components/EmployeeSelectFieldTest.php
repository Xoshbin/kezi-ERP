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
    $this->setupWithConfiguredCompany();

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
    $component = EmployeeSelectField::make('employee_id');

    /** @var \Closure $createOptionUsing */
    $createOptionUsing = $component->getCreateOptionUsing();
    expect($createOptionUsing)->toBeCallable();

    /** @var \App\Models\Company $company */
    $company = $this->company;

    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'employee_number' => 'EMP2024001',
        'hire_date' => now()->format('Y-m-d'),
    ];

    $id = $createOptionUsing($data);

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
