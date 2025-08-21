<?php

namespace App\Filament\Clusters\HumanResources\Resources\LeaveTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LeaveTypeForm
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
                TextInput::make('code')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('default_days_per_year')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('requires_approval')
                    ->required(),
                Toggle::make('is_paid')
                    ->required(),
                Toggle::make('carries_forward')
                    ->required(),
                TextInput::make('max_carry_forward_days')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('max_consecutive_days')
                    ->numeric(),
                TextInput::make('min_notice_days')
                    ->required()
                    ->numeric()
                    ->default(1),
                Toggle::make('requires_documentation')
                    ->required(),
                TextInput::make('color')
                    ->required()
                    ->default('#3B82F6'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
