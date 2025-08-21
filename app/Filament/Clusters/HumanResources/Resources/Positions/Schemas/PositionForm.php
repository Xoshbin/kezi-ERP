<?php

namespace App\Filament\Clusters\HumanResources\Resources\Positions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Select::make('department_id')
                    ->relationship('department', 'name'),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('requirements'),
                TextInput::make('responsibilities'),
                TextInput::make('employment_type')
                    ->required()
                    ->default('full_time'),
                TextInput::make('level')
                    ->required()
                    ->default('entry'),
                TextInput::make('min_salary')
                    ->numeric(),
                TextInput::make('max_salary')
                    ->numeric(),
                Select::make('salary_currency_id')
                    ->relationship('salaryCurrency', 'name'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
