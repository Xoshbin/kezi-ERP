<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExpenseReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('hr::expense_report.sections.report_details'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('report_number')
                                    ->label(__('hr::expense_report.fields.report_number'))
                                    ->weight('bold'),
                                TextEntry::make('status')
                                    ->label(__('hr::expense_report.fields.status'))
                                    ->badge(),
                                TextEntry::make('report_date')
                                    ->label(__('hr::expense_report.fields.report_date'))
                                    ->date(),
                                TextEntry::make('employee.full_name')
                                    ->label(__('hr::expense_report.fields.employee')),
                                TextEntry::make('cashAdvance.advance_number')
                                    ->label(__('hr::expense_report.fields.cash_advance')),
                                TextEntry::make('total_amount')
                                    ->label(__('hr::expense_report.fields.total_amount'))
                                    ->money(fn ($record) => $record->cashAdvance->currency->code),
                            ]),
                    ]),
                Section::make(__('hr::expense_report.sections.expense_lines'))
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->label(__('hr::expense_report.fields.lines'))
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('expense_date')
                                            ->label(__('hr::expense_report.lines.date'))
                                            ->date(),
                                        TextEntry::make('description')
                                            ->label(__('hr::expense_report.lines.description')),
                                        TextEntry::make('expenseAccount.name')
                                            ->label(__('hr::expense_report.lines.expense_account')),
                                        TextEntry::make('amount')
                                            ->label(__('hr::expense_report.lines.amount'))
                                            ->money(fn ($record) => $record->expenseReport->cashAdvance->currency->code),
                                    ]),
                            ])
                            ->columns(1),
                    ]),
            ]);
    }
}
