<?php

namespace Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Jmeryar\ProjectManagement\Enums\BillingType;
use Jmeryar\ProjectManagement\Enums\ProjectStatus;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('projectmanagement::project.form.sections.project_details'))
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
                            ->relationship('manager', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
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
                            ->label(__('projectmanagement::project.form.labels.budget'))
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
                Section::make(__('projectmanagement::project.form.sections.system'))
                    ->collapsed()
                    ->schema([
                        Select::make('company_id')
                            ->relationship('company', 'name')
                            ->required()
                            ->default(fn () => auth()->user()->current_company_id ?? null)
                            ->disabled()
                            ->dehydrated(),
                        Select::make('analytic_account_id')
                            ->relationship('analyticAccount', 'name')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText(__('projectmanagement::project.form.helper_texts.analytic_account_auto')),
                    ]),
            ]);
    }
}
