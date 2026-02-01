<?php

namespace Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ProjectBudgetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Select::make('project_id')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('start_date'),
                        DatePicker::make('end_date'),
                        Textarea::make('description')
                            ->columnSpanFull(),
                        Hidden::make('company_id')
                            ->default(fn () => Filament::getTenant()?->id),
                    ])->columns(2),

                Repeater::make('lines')
                    ->relationship('lines')
                    ->schema([
                        Select::make('account_id')
                            ->relationship('account', 'name')
                            ->required()
                            ->searchable(),
                        TextInput::make('description'),
                        TextInput::make('budgeted_amount')
                            ->numeric()
                            ->required()
                            ->default(0),
                        TextInput::make('actual_amount')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        Hidden::make('company_id')
                            ->default(fn () => Filament::getTenant()?->id),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->defaultItems(1),
            ]);
    }
}
