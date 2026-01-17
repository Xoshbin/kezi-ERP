<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Schemas;

use Filament\Schemas\Schema;

class CashAdvanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make(__('hr::cash_advance.sections.request_details'))
                    ->schema([
                        \Filament\Forms\Components\Select::make('employee_id')
                            ->relationship('employee', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                            ->searchable()
                            ->preload()
                            ->required(),
                        \Filament\Forms\Components\Select::make('currency_id')
                            ->relationship('currency', 'code')
                            ->default(fn () => \App\Models\Company::first()?->base_currency_id)
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('requested_amount')
                            ->numeric()
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
