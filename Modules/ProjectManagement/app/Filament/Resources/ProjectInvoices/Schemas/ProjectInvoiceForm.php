<?php

namespace Modules\ProjectManagement\Filament\Resources\ProjectInvoices\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice Details')
                    ->schema([
                        Select::make('project_id')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        DatePicker::make('invoice_date')
                            ->default(now())
                            ->required(),
                        Grid::make()
                            ->schema([
                                DatePicker::make('period_start')
                                    ->required(),
                                DatePicker::make('period_end')
                                    ->required()
                                    ->afterOrEqual('period_start'),
                            ]),
                    ]),
                Section::make('Generation Options')
                    ->visible(fn ($operation) => $operation === 'create')
                    ->schema([
                        Toggle::make('include_labor')
                            ->default(true)
                            ->label('Include Labor Costs (Timesheets)'),
                        Toggle::make('include_expenses')
                            ->default(true)
                            ->label('Include Expenses (Journal Entries)'),
                    ]),
                Section::make('Financials')
                    ->visible(fn ($operation) => $operation !== 'create')
                    ->schema([
                        TextInput::make('labor_amount')
                            ->numeric()
                            ->prefix('$')
                            ->readOnly(),
                        TextInput::make('expense_amount')
                            ->numeric()
                            ->prefix('$')
                            ->readOnly(),
                        TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('$')
                            ->readOnly(),
                    ])->columns(3),
            ]);
    }
}
