<?php

namespace Modules\ProjectManagement\Filament\Resources\Timesheets\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Modules\ProjectManagement\Enums\TimesheetStatus;

class TimesheetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Select::make('employee_id')
                            ->relationship('employee', 'first_name') // Assuming first_name, check Employee model if name exists
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                            ->required()
                            ->searchable()
                            ->preload(),
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
                            ->label('Task')
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
                    ->defaultItems(1),
            ]);
    }
}
