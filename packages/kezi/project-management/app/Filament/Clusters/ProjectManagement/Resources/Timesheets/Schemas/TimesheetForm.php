<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Timesheets\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Kezi\ProjectManagement\Enums\TimesheetStatus;

class TimesheetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        \Kezi\HR\Filament\Forms\Components\EmployeeSelectField::make('employee_id'),
                        Select::make('status')
                            ->options(TimesheetStatus::class)
                            ->default(TimesheetStatus::Draft)
                            ->disabled() // Status managed by actions
                            ->dehydrated(),
                        DatePicker::make('start_date')
                            ->required(),
                        DatePicker::make('end_date')
                            ->required(),
                        TextInput::make('total_hours')
                            ->disabled()
                            ->dehydrated(false) // Calculated by backend
                            ->numeric()
                            ->default(0),
                    ])->columns(2),

                Repeater::make('lines')
                    ->relationship()
                    ->schema([
                        Select::make('project_id')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('project_task_id', null)),
                        Select::make('project_task_id')
                            ->label(__('projectmanagement::project.form.labels.task'))
                            ->relationship('projectTask', 'name', function ($query, Get $get) {
                                $projectId = $get('project_id');
                                if ($projectId) {
                                    $query->where('project_id', $projectId);
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->required(false),
                        DatePicker::make('date')
                            ->required()
                            ->default(now()),
                        TextInput::make('hours')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0.1)
                            ->maxValue(24),
                        TextInput::make('description')
                            ->columnSpan(2),
                        Toggle::make('is_billable')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(7)
                    ->columnSpanFull()
                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                        $data['company_id'] = \Filament\Facades\Filament::getTenant()?->id;

                        return $data;
                    }),
            ]);
    }
}
