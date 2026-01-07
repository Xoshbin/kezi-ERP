<?php

namespace Modules\ProjectManagement\Filament\Resources\ProjectBudgets\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                            ->relationship('account', 'name') // Assuming linking to general accounts or analytic accounts?
                            // Implementation plan said "Account (Chart of Accounts)".
                            // ProjectBudgetLine has account_id.
                            ->required()
                            ->searchable(),
                        TextInput::make('description'),
                        TextInput::make('budgeted_amount')
                            ->numeric()
                            ->required()
                            ->default(0)
                            // Assuming base currency (2 decimals)
                            ->formatStateUsing(fn ($state) => $state ? number_format($state / 100, 2, '.', '') : '0.00')
                            ->dehydrateStateUsing(fn ($state) => (int) ($state * 100)),
                        TextInput::make('actual_amount')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($state) => $state ? number_format($state / 100, 2, '.', '') : '0.00'),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->defaultItems(1),
            ]);
    }
}
