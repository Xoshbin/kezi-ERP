<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Schemas;

use Filament\Schemas\Schema;

class CashAdvanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make(__('hr::cash_advance.sections.request_details'))
                    ->schema([
                        \Kezi\HR\Filament\Forms\Components\EmployeeSelectField::make('employee_id')
                            ->label(__('hr::cash_advance.fields.employee')),
                        \Kezi\Foundation\Filament\Forms\Components\CurrencySelectField::make('currency_id')
                            ->default(fn () => \Filament\Facades\Filament::getTenant()?->currency_id)
                            ->required(),
                        \Kezi\Foundation\Filament\Forms\Components\MoneyInput::make('requested_amount')
                            ->currencyField('currency_id')
                            ->label(__('hr::cash_advance.requested_amount'))
                            ->required(),
                        \Filament\Forms\Components\DatePicker::make('expected_return_date')
                            ->label(__('hr::cash_advance.expected_return_date')),
                        \Filament\Forms\Components\Textarea::make('purpose')
                            ->required()
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
