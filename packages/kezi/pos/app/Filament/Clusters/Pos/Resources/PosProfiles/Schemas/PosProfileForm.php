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
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Models\StockLocation;

class PosProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('pos::pos_profile.basic_configuration'))
                    ->schema([
                        Hidden::make('company_id')
                            ->default(fn () => \Filament\Facades\Filament::getTenant()?->id),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Select::make('type')
                            ->options([
                                'retail' => __('pos::pos_profile.retail'),
                                'hospitality' => __('pos::pos_profile.hospitality'),
                                'service' => __('pos::pos_profile.service'),
                            ])
                            ->required()
                            ->live(),

                        Toggle::make('is_active')
                            ->label(__('pos::pos_profile.is_active'))
                            ->default(true),

                        Select::make('stock_location_id')
                            ->label(__('pos::pos_profile.stock_location'))
                            ->helperText(__('pos::pos_profile.stock_location_helper'))
                            ->options(fn () => StockLocation::query()
                                ->where('company_id', \Filament\Facades\Filament::getTenant()?->id)
                                ->where('type', StockLocationType::Internal)
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ]),

                Section::make(__('pos::pos_profile.feature_modules'))
                    ->schema([
                        CheckboxList::make('features')
                            ->label(__('pos::pos_profile.features'))
                            ->options([
                                'tables' => __('pos::pos_profile.tables'),
                                'barcodes' => __('pos::pos_profile.barcodes'),
                                'split_bill' => __('pos::pos_profile.split_bill'),
                                'kitchen_printer' => __('pos::pos_profile.kitchen_printer'),
                                'inventory_check' => __('pos::pos_profile.inventory_check'),
                            ])
                            ->columns(2),
                    ]),

                Section::make(__('pos::pos_profile.terminal_settings'))
                    ->schema([
                        KeyValue::make('settings')
                            ->keyLabel(__('pos::pos_profile.option'))
                            ->valueLabel(__('pos::pos_profile.value')),
                    ]),
            ]);
    }
}
