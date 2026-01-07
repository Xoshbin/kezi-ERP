<?php

namespace Modules\ProjectManagement\Filament\Resources\Projects\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\ProjectManagement\Enums\BillingType;
use Modules\ProjectManagement\Enums\ProjectStatus;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Project Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('manager_id')
                            ->relationship('manager', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->options(ProjectStatus::class)
                            ->default(ProjectStatus::Draft)
                            ->required(),
                        Grid::make()
                            ->schema([
                                DatePicker::make('start_date'),
                                DatePicker::make('end_date'),
                            ])->columns(2),
                        Textarea::make('description')
                            ->columnSpanFull(),
                    ]),
                Section::make('Billing & Budget')
                    ->columns(2)
                    ->schema([
                        TextInput::make('budget_amount')
                            ->label('Budget')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Select::make('billing_type')
                            ->options(BillingType::class)
                            ->default(BillingType::TimeAndMaterials)
                            ->required(),
                        Toggle::make('is_billable')
                            ->required()
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
                Section::make('System')
                    ->collapsed()
                    ->schema([
                        Select::make('company_id')
                            ->relationship('company', 'name')
                            ->required()
                            ->default(fn () => auth()->user()->current_company_id ?? null)
                            ->disabled(),
                        Select::make('analytic_account_id')
                            ->relationship('analyticAccount', 'name')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Automatically created on project creation'),
                    ]),
            ]);
    }
}
