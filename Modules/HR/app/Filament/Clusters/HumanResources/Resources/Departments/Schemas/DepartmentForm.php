<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\Departments\Schemas;

use App\Models\Department;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\HR\Models\Employee;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('department.basic_information'))
                ->description(__('department.basic_information_description'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('department.name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    TranslatableSelect::forModel('parent_department_id', Department::class)
                        ->searchable()
                        ->preload()
                        ->label(__('department.parent_department'))
                        ->searchableFields(['name'])
                        ->preload()
                        ->columnSpan(1),

                    Textarea::make('description')
                        ->label(__('department.description'))
                        ->maxLength(1000)
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('department.management'))
                ->description(__('department.management_description'))
                ->schema([
                    TranslatableSelect::forModel('manager_id', Employee::class)
                        ->label(__('department.manager'))
                        ->searchable()
                        ->searchableFields(['first_name', 'last_name', 'employee_number'])
                        ->preload()
                        ->columnSpan(1),

                    Toggle::make('is_active')
                        ->label(__('department.is_active'))
                        ->default(true)
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
