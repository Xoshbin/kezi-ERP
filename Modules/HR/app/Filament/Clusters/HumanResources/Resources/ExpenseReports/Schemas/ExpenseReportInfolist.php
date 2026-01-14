<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Schemas;

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
                Section::make('Report Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('report_number')
                                    ->weight('bold'),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('report_date')
                                    ->date(),
                                TextEntry::make('employee.full_name'),
                                TextEntry::make('cashAdvance.advance_number')
                                    ->label(__('hr::expense.cash_advance')),
                                TextEntry::make('total_amount')
                                    ->money(fn ($record) => $record->cashAdvance->currency->code),
                            ]),
                    ]),
                Section::make('Expense Lines')
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('expense_date')->date(),
                                        TextEntry::make('description'),
                                        TextEntry::make('expenseAccount.name')->label(__('hr::expense.account')),
                                        TextEntry::make('amount')
                                            ->money(fn ($record) => $record->expenseReport->cashAdvance->currency->code),
                                    ]),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }
}
