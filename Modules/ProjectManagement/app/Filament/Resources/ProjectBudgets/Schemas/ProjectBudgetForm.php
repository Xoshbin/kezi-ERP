<?php

namespace Modules\ProjectManagement\Filament\Resources\ProjectBudgets\Schemas;

use Filament\Forms\Components\DatePicker;
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
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->defaultItems(1),
            ]);
    }
}
