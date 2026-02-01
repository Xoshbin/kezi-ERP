<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\Positions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Jmeryar\Foundation\Filament\Forms\Components\MoneyInput;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\HR\Models\Department;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class PositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('hr::position.basic_information'))
                ->description(__('hr::position.basic_information_description'))
                ->schema([
                    TextInput::make('title')
                        ->label(__('hr::position.title'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    TranslatableSelect::forModel('department_id', Department::class)
                        ->label(__('hr::position.department'))
                        ->searchable()
                        ->searchableFields(['name'])
                        ->preload()
                        ->columnSpan(1),

                    Textarea::make('description')
                        ->label(__('hr::position.description'))
                        ->maxLength(1000)
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('requirements')
                        ->label(__('hr::position.requirements'))
                        ->maxLength(2000)
                        ->rows(4)
                        ->columnSpan(1),

                    Textarea::make('responsibilities')
                        ->label(__('hr::position.responsibilities'))
                        ->maxLength(2000)
                        ->rows(4)
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('hr::position.employment_details'))
                ->description(__('hr::position.employment_details_description'))
                ->schema([
                    Select::make('employment_type')
                        ->label(__('hr::position.employment_type'))
                        ->options([
                            'full_time' => __('hr::position.employment_type_full_time'),
                            'part_time' => __('hr::position.employment_type_part_time'),
                            'contract' => __('hr::position.employment_type_contract'),
                            'intern' => __('hr::position.employment_type_intern'),
                        ])
                        ->required()
                        ->default('full_time')
                        ->columnSpan(1),

                    Select::make('level')
                        ->label(__('hr::position.level'))
                        ->options([
                            'entry' => __('hr::position.level_entry'),
                            'junior' => __('hr::position.level_junior'),
                            'mid' => __('hr::position.level_mid'),
                            'senior' => __('hr::position.level_senior'),
                            'lead' => __('hr::position.level_lead'),
                            'manager' => __('hr::position.level_manager'),
                            'director' => __('hr::position.level_director'),
                        ])
                        ->required()
                        ->default('entry')
                        ->columnSpan(1),

                    Toggle::make('is_active')
                        ->label(__('hr::position.is_active'))
                        ->default(true)
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('hr::position.salary_range'))
                ->description(__('hr::position.salary_range_description'))
                ->schema([
                    TranslatableSelect::forModel('currency_id', Currency::class)
                        ->searchable()
                        ->label(__('hr::position.salary_currency'))
                        ->searchableFields(['name', 'code'])
                        ->preload()
                        ->live()
                        ->columnSpan(3),

                    MoneyInput::make('min_salary')
                        ->label(__('hr::position.min_salary'))
                        ->currencyField('currency_id')
                        ->columnSpan(1),

                    MoneyInput::make('max_salary')
                        ->label(__('hr::position.max_salary'))
                        ->currencyField('currency_id')
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }
}
