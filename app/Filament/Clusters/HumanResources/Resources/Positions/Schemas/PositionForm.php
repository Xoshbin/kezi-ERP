<?php

namespace App\Filament\Clusters\HumanResources\Resources\Positions\Schemas;

use App\Models\Department;
use App\Models\Currency;
use Filament\Facades\Filament;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Support\TranslatableSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('position.basic_information'))
                ->description(__('position.basic_information_description'))
                ->schema([
                    TextInput::make('title')
                        ->label(__('position.title'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    TranslatableSelect::make('department_id', Department::class, __('position.department'))
                        ->columnSpan(1),

                    Textarea::make('description')
                        ->label(__('position.description'))
                        ->maxLength(1000)
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('requirements')
                        ->label(__('position.requirements'))
                        ->maxLength(2000)
                        ->rows(4)
                        ->columnSpan(1),

                    Textarea::make('responsibilities')
                        ->label(__('position.responsibilities'))
                        ->maxLength(2000)
                        ->rows(4)
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('position.employment_details'))
                ->description(__('position.employment_details_description'))
                ->schema([
                    Select::make('employment_type')
                        ->label(__('position.employment_type'))
                        ->options([
                            'full_time' => __('position.employment_type_full_time'),
                            'part_time' => __('position.employment_type_part_time'),
                            'contract' => __('position.employment_type_contract'),
                            'intern' => __('position.employment_type_intern'),
                        ])
                        ->required()
                        ->default('full_time')
                        ->columnSpan(1),

                    Select::make('level')
                        ->label(__('position.level'))
                        ->options([
                            'entry' => __('position.level_entry'),
                            'junior' => __('position.level_junior'),
                            'mid' => __('position.level_mid'),
                            'senior' => __('position.level_senior'),
                            'lead' => __('position.level_lead'),
                            'manager' => __('position.level_manager'),
                            'director' => __('position.level_director'),
                        ])
                        ->required()
                        ->default('entry')
                        ->columnSpan(1),

                    Toggle::make('is_active')
                        ->label(__('position.is_active'))
                        ->default(true)
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('position.salary_range'))
                ->description(__('position.salary_range_description'))
                ->schema([
                    TranslatableSelect::make('salary_currency_id', Currency::class, __('position.salary_currency'))
                        ->live()
                        ->default(fn() => Filament::getTenant()?->currency_id)
                        ->columnSpan(3),

                    MoneyInput::make('min_salary')
                        ->label(__('position.min_salary'))
                        ->currencyField('salary_currency_id')
                        ->columnSpan(1),

                    MoneyInput::make('max_salary')
                        ->label(__('position.max_salary'))
                        ->currencyField('salary_currency_id')
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }
}
