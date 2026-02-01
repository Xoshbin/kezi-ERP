<?php

namespace Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Jmeryar\Foundation\Filament\Forms\Components\MoneyInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('projectmanagement::project.form.sections.invoice_details'))
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
                        Hidden::make('company_id')
                            ->default(fn () => Filament::getTenant()?->id),
                    ]),
                Section::make(__('projectmanagement::project.form.sections.generation_options'))
                    ->visible(fn ($operation) => $operation === 'create')
                    ->schema([
                        Toggle::make('include_labor')
                            ->default(true)
                            ->label('Include Labor Costs (Timesheets)'),
                        Toggle::make('include_expenses')
                            ->default(true)
                            ->label('Include Expenses (Journal Entries)'),
                    ]),
                Section::make(__('projectmanagement::project.form.sections.financials'))
                    ->visible(fn ($operation) => $operation !== 'create')
                    ->schema([
                        MoneyInput::make('labor_amount')
                            ->readOnly(),
                        MoneyInput::make('expense_amount')
                            ->readOnly(),
                        MoneyInput::make('total_amount')
                            ->readOnly(),
                    ])->columns(3),
            ]);
    }
}
