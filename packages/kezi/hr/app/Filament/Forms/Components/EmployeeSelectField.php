<?php

namespace Kezi\HR\Filament\Forms\Components;

use Filament\Actions\Action;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Employees\EmployeeResource;
use Kezi\HR\Models\Employee;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class EmployeeSelectField extends TranslatableSelect
{
    protected ?\Closure $customQueryModifier = null;

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

        $this->modifyQueryUsing(function ($query) {
            $tenant = \Filament\Facades\Filament::getTenant();

            if ($tenant instanceof \Illuminate\Database\Eloquent\Model) {
                $query->where('company_id', $tenant->getKey());
            }

            if ($this->customQueryModifier) {
                $query = ($this->customQueryModifier)($query) ?? $query;
            }

            return $query;
        });

        $this->getOptionLabelFromRecordUsing(fn (Employee $record): string => $record->display_name);

        $this->actionSchemaModel(Employee::class);

        $this->createOptionForm(function (\Filament\Schemas\Schema $schema) {
            return EmployeeResource::form($schema)->getComponents();
        });

        $this->createOptionModalHeading(__('hr::employee.create'));

        $this->createOptionAction(function (Action $action) {
            return $action->modalWidth('7xl');
        });

        $this->createOptionUsing(function (array $data): int {
            $tenant = \Filament\Facades\Filament::getTenant();

            if ($tenant instanceof \Illuminate\Database\Eloquent\Model) {
                $data['company_id'] = $tenant->getKey();
            }

            /** @var Employee $employee */
            $employee = Employee::query()->create($data);

            return (int) $employee->getKey();
        });
    }

    public function query(\Closure $callback): static
    {
        $this->customQueryModifier = $callback;

        return $this;
    }

    public function getCustomQueryModifier(): ?\Closure
    {
        return $this->customQueryModifier;
    }

    public static function make(?string $name = null): static
    {
        if ($name === null) {
            throw new \InvalidArgumentException('EmployeeSelectField requires a name.');
        }

        return parent::make($name);
    }
}
