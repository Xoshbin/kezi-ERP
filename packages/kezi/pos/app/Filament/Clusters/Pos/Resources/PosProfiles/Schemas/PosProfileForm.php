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
use Kezi\Accounting\Filament\Forms\Components\AccountSelectField;
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
                            ->default(fn () => \Filament\Facades\Filament::getTenant()?->getKey()),

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
                                ->where('company_id', \Filament\Facades\Filament::getTenant()?->getKey())
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

                Section::make(__('pos::pos_profile.accounting_settings'))
                    ->schema([
                        AccountSelectField::make('default_income_account_id')
                            ->label(__('pos::pos_profile.default_income_account'))
                            ->helperText(__('pos::pos_profile.default_income_account_helper'))
                            ->accountFilter('income')
                            ->createOptionDefaultType(\Kezi\Accounting\Enums\Accounting\AccountType::Income)
                            ->required(),

                        Select::make('default_payment_journal_id')
                            ->label(__('pos::pos_profile.default_payment_journal'))
                            ->helperText(__('pos::pos_profile.default_payment_journal_helper'))
                            ->options(fn () => \Kezi\Accounting\Models\Journal::query()
                                ->where('company_id', \Filament\Facades\Filament::getTenant()?->getKey())
                                ->whereIn('type', [
                                    \Kezi\Accounting\Enums\Accounting\JournalType::Cash,
                                    \Kezi\Accounting\Enums\Accounting\JournalType::Bank,
                                ])
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ]),

                Section::make(__('pos::pos_profile.return_policy'))
                    ->schema([
                        Toggle::make('return_policy.enabled')
                            ->label(__('pos::pos_profile.return_policy_enabled'))
                            ->helperText(__('pos::pos_profile.return_policy_enabled_helper'))
                            ->live(),

                        TextInput::make('return_policy.time_limit_days')
                            ->label(__('pos::pos_profile.time_limit_days'))
                            ->helperText(__('pos::pos_profile.time_limit_days_helper'))
                            ->numeric()
                            ->default(30)
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('return_policy.enabled')),

                        Toggle::make('return_policy.require_receipt')
                            ->label(__('pos::pos_profile.require_receipt'))
                            ->default(true)
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('return_policy.enabled')),

                        Toggle::make('return_policy.require_manager_approval')
                            ->label(__('pos::pos_profile.require_manager_approval'))
                            ->live()
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('return_policy.enabled')),

                        TextInput::make('return_policy.manager_approval_threshold')
                            ->label(__('pos::pos_profile.manager_approval_threshold'))
                            ->helperText(__('pos::pos_profile.manager_approval_threshold_helper'))
                            ->numeric()
                            ->default(0)
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('return_policy.enabled') && $get('return_policy.require_manager_approval')),

                        TextInput::make('return_policy.restocking_fee_percentage')
                            ->label(__('pos::pos_profile.restocking_fee_percentage'))
                            ->numeric()
                            ->inputMode('decimal')
                            ->default(0)
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('return_policy.enabled')),

                        CheckboxList::make('return_policy.allowed_refund_methods')
                            ->label(__('pos::pos_profile.allowed_refund_methods'))
                            ->options([
                                'original_method' => __('pos::pos_profile.original_method'),
                                'store_credit' => __('pos::pos_profile.store_credit'),
                                'cash' => __('pos::pos_profile.cash'),
                            ])
                            ->default(['original_method'])
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('return_policy.enabled')),

                        Select::make('return_policy.default_restock_location_id')
                            ->label(__('pos::pos_profile.default_restock_location'))
                            ->options(fn () => StockLocation::query()
                                ->where('company_id', \Filament\Facades\Filament::getTenant()?->getKey())
                                ->where('type', StockLocationType::Internal)
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('return_policy.enabled')),

                        Select::make('return_policy.damaged_items_location_id')
                            ->label(__('pos::pos_profile.damaged_items_location'))
                            ->options(fn () => StockLocation::query()
                                ->where('company_id', \Filament\Facades\Filament::getTenant()?->getKey())
                                ->where('type', StockLocationType::Internal)
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('return_policy.enabled')),
                    ])->columns(2),
            ]);
    }
}
