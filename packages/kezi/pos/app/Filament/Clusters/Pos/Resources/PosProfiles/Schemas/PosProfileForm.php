<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Resources\PosProfiles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PosProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Configuration')
                    ->schema([
                        Hidden::make('company_id')
                            ->default(fn () => Auth::user()?->company_id),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Select::make('type')
                            ->options([
                                'retail' => 'Retail',
                                'hospitality' => 'Hospitality',
                                'service' => 'Service',
                            ])
                            ->required()
                            ->live(),

                        Toggle::make('is_active')
                            ->default(true),
                    ]),

                Section::make('Feature Modules')
                    ->schema([
                        CheckboxList::make('features')
                            ->options([
                                'tables' => 'Table Management',
                                'barcodes' => 'Barcode Scanning',
                                'split_bill' => 'Split Billing',
                                'kitchen_printer' => 'Kitchen Printing',
                                'inventory_check' => 'Real-time Stock Check',
                            ])
                            ->columns(2),
                    ]),

                Section::make('Terminal Settings')
                    ->schema([
                        KeyValue::make('settings')
                            ->keyLabel('Option')
                            ->valueLabel('Value'),
                    ]),
            ]);
    }
}
