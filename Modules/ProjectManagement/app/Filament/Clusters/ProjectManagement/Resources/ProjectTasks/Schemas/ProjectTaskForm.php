<?php

namespace Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Schemas\Schema;
use Modules\ProjectManagement\Enums\TaskStatus;

class ProjectTaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Select::make('project_id')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('parent_task_id', null)),
                        Select::make('parent_task_id')
                            ->label('Parent Task')
                            ->relationship('parentTask', 'name', function ($query, Get $get) {
                                $projectId = $get('project_id');
                                if ($projectId) {
                                    $query->where('project_id', $projectId);
                                }
                            })
                            ->searchable()
                            ->preload(),
                        Select::make('assigned_to')
                            ->relationship('assignee', 'first_name') // Assuming first_name for now
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->options(TaskStatus::class)
                            ->default(TaskStatus::Pending)
                            ->required(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        DatePicker::make('start_date'),
                        DatePicker::make('due_date'),
                        TextInput::make('estimated_hours')
                            ->numeric()
                            ->default(0),
                        TextInput::make('progress_percentage')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%'),
                        RichEditor::make('description')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
