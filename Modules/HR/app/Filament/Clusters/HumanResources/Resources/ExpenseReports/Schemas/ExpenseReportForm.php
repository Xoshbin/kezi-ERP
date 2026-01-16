<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                                Select::make('expense_account_id')
                                    ->label(__('hr::expense_report.lines.expense_account'))
                                    ->options(\Modules\Accounting\Models\Account::where('type', \Modules\Accounting\Enums\Accounting\AccountType::Expense)->get()->pluck('name', 'id'))
                                    ->searchable()
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
                                Select::make('partner_id')
                                    ->label(__('hr::expense_report.lines.partner'))
                                    ->options(\Modules\Foundation\Models\Partner::get()->pluck('name', 'id'))
                                    ->searchable(),
                                TextInput::make('receipt_reference')
                                    ->label(__('hr::expense_report.lines.receipt')),
                            ])
                            ->columns(4)
                            ->defaultItems(0),
                    ]),
            ]);
    }
}
