<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Kezi\Accounting\Filament\Forms\Components\AccountSelectField;

class ExpenseReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('hr::expense_report.sections.report_details'))
                    ->schema([
                        Select::make('cash_advance_id')
                            ->label(__('hr::expense_report.fields.cash_advance'))
                            ->relationship('cashAdvance', 'advance_number')
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('report_date')
                            ->label(__('hr::expense_report.fields.report_date'))
                            ->required()
                            ->default(now()),
                        Textarea::make('notes')
                            ->label(__('hr::expense_report.fields.notes'))
                            ->columnSpanFull(),
                    ])->columns(2),
                Section::make(__('hr::expense_report.sections.expense_lines'))
                    ->schema([
                        Repeater::make('lines')
                            ->schema([
                                AccountSelectField::make('expense_account_id')
                                    ->label(__('hr::expense_report.lines.expense_account'))
                                    ->accountFilter('expense')
                                    ->required()
                                    ->columnSpan(2),
                                DatePicker::make('expense_date')
                                    ->label(__('hr::expense_report.lines.date'))
                                    ->required()
                                    ->default(now()),
                                TextInput::make('amount')
                                    ->label(__('hr::expense_report.lines.amount'))
                                    ->numeric()
                                    ->required()
                                    ->formatStateUsing(fn ($state) => $state instanceof \Brick\Money\Money ? $state->getAmount()->toFloat() : $state),
                                TextInput::make('description')
                                    ->label(__('hr::expense_report.lines.description'))
                                    ->required()
                                    ->columnSpan(2),
                                \Kezi\Foundation\Filament\Forms\Components\PartnerSelectField::make('partner_id')
                                    ->label(__('hr::expense_report.lines.partner')),
                                TextInput::make('receipt_reference')
                                    ->label(__('hr::expense_report.lines.receipt')),
                            ])
                            ->columns(4)
                            ->defaultItems(0),
                    ]),
            ]);
    }
}
