<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Schemas;

use Filament\Schemas\Schema;

class ExpenseReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Report Details')
                    ->schema([
                        \Filament\Forms\Components\Select::make('cash_advance_id')
                            ->relationship('cashAdvance', 'advance_number')
                            ->searchable()
                            ->preload()
                            ->required(),
                        \Filament\Forms\Components\DatePicker::make('report_date')
                            ->required()
                            ->default(now()),
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ])->columns(2),
                \Filament\Schemas\Components\Section::make('Expense Lines')
                    ->schema([
                        \Filament\Forms\Components\Repeater::make('lines')
                            // ->relationship('lines')
                            ->schema([
                                \Filament\Forms\Components\Select::make('expense_account_id')
                                    ->label('Expense Account')
                                    ->options(\Modules\Accounting\Models\Account::where('type', \Modules\Accounting\Enums\Accounting\AccountType::Expense)->get()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(2),
                                \Filament\Forms\Components\DatePicker::make('expense_date')
                                    ->required()
                                    ->default(now()),
                                \Filament\Forms\Components\TextInput::make('amount')
                                    ->numeric()
                                    ->required()
                                    ->label('Amount')
                                    ->formatStateUsing(fn ($state) => $state instanceof \Brick\Money\Money ? $state->getAmount()->toFloat() : $state),
                                \Filament\Forms\Components\TextInput::make('description')
                                    ->required()
                                    ->columnSpan(2),
                                \Filament\Forms\Components\Select::make('partner_id')
                                    ->label('Vendor (Optional)')
                                    ->options(\Modules\Foundation\Models\Partner::get()->pluck('name', 'id'))
                                    ->searchable(),
                                \Filament\Forms\Components\TextInput::make('receipt_reference'),
                            ])
                            ->columns(4)
                            ->defaultItems(0),
                    ]),
            ]);
    }
}
