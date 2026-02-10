<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\DeductionRuleResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DeductionRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('hr::payroll.deduction_rule_details'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('hr::payroll.deduction_name'))
                        ->required()
                        ->maxLength(255),

                    TextInput::make('code')
                        ->label(__('hr::payroll.deduction_code'))
                        ->helperText(__('hr::payroll.deduction_code_helper'))
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: fn (\Illuminate\Validation\Rules\Unique $rule) => $rule->where('company_id', \Filament\Facades\Filament::getTenant()?->id)
                        )
                        ->maxLength(100),

                    Select::make('type')
                        ->label(__('hr::payroll.deduction_type'))
                        ->options([
                            'percentage' => __('hr::payroll.type_percentage'),
                            'fixed_amount' => __('hr::payroll.type_fixed_amount'),
                        ])
                        ->required()
                        ->live(),

                    TextInput::make('value')
                        ->label(__('hr::payroll.percentage_value'))
                        ->numeric()
                        ->placeholder('0.10 for 10%')
                        ->visible(fn (Get $get): bool => $get('type') === 'percentage')
                        ->required(fn (Get $get): bool => $get('type') === 'percentage'),

                    TextInput::make('amount')
                        ->label(__('hr::payroll.fixed_amount'))
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('type') === 'fixed_amount')
                        ->required(fn (Get $get): bool => $get('type') === 'fixed_amount'),

                    Select::make('currency_code')
                        ->label(__('hr::payroll.currency'))
                        ->options(\Kezi\Foundation\Models\Currency::pluck('name', 'code'))
                        ->searchable()
                        ->visible(fn (Get $get): bool => $get('type') === 'fixed_amount'),

                    Select::make('liability_account_id')
                        ->label(__('hr::payroll.liability_account'))
                        ->relationship('liabilityAccount', 'name')
                        ->searchable()
                        ->preload(),

                    Toggle::make('is_statutory')
                        ->label(__('hr::payroll.is_statutory'))
                        ->default(false),

                    Toggle::make('is_active')
                        ->label(__('hr::payroll.is_active'))
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }
}
