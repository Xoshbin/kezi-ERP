<?php

namespace App\Filament\Clusters\HumanResources\Resources\Departments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('parent_department_id')
                    ->relationship('parentDepartment', 'name'),
                Select::make('manager_id')
                    ->relationship('manager', 'name'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
