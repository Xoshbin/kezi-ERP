<?php

namespace Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Modules\ProjectManagement\Enums\TaskStatus;

class ProjectTaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Select::make('project_id')
                            ->label(__('projectmanagement::project.task.project'))
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('parent_task_id', null)),
                        Select::make('parent_task_id')
                            ->label(__('projectmanagement::project.task.parent_task'))
                            ->relationship('parentTask', 'name', function ($query, $get) {
                                $projectId = $get('project_id');
                                if ($projectId) {
                                    $query->where('project_id', $projectId);
                                }
                            })
                            ->searchable()
                            ->preload(),
                        Select::make('assigned_to')
                            ->label(__('projectmanagement::project.task.assigned_to'))
                            ->relationship('assignedEmployee', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->label(__('projectmanagement::project.task.status'))
                            ->options(TaskStatus::class)
                            ->default(TaskStatus::Pending)
                            ->required(),
                        TextInput::make('name')
                            ->label(__('projectmanagement::project.task.name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        DatePicker::make('start_date')
                            ->label(__('projectmanagement::project.task.start_date')),
                        DatePicker::make('due_date')
                            ->label(__('projectmanagement::project.task.due_date')),
                        TextInput::make('estimated_hours')
                            ->label(__('projectmanagement::project.task.estimated_hours'))
                            ->numeric()
                            ->default(0),
                        TextInput::make('progress_percentage')
                            ->label(__('projectmanagement::project.task.progress_percentage'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%'),
                        RichEditor::make('description')
                            ->label(__('projectmanagement::project.task.description'))
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
