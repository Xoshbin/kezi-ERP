<?php

namespace Kezi\HR\Filament\Forms\Components;

use Filament\Actions\Action;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Employees\EmployeeResource;
use Kezi\HR\Models\Employee;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class EmployeeSelectField extends TranslatableSelect
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->modelClass = Employee::class;
        $this->relationshipTitleAttribute = 'first_name';
        $this->configureForModel();

        $this->label(__('hr::employee.label'));

        // Match EmployeeResource search fields
        $this->searchableFields(['first_name', 'last_name', 'email', 'employee_number']);
        $this->searchable();
        $this->preload();
        $this->required();

        $this->getOptionLabelFromRecordUsing(fn (Employee $record): string => $record->full_name);

        $this->actionSchemaModel(Employee::class);

        $this->createOptionForm(function (\Filament\Schemas\Schema $schema) {
            return EmployeeResource::form($schema)->getComponents();
        });

        $this->createOptionModalHeading(__('hr::employee.create'));

        $this->createOptionAction(function (Action $action) {
            return $action->modalWidth('7xl');
        });

        $this->createOptionUsing(function (array $data): int {
            $employee = Employee::create($data);

            return $employee->getKey();
        });
    }

    public static function make(?string $name = null): static
    {
        return parent::make($name);
    }
}
