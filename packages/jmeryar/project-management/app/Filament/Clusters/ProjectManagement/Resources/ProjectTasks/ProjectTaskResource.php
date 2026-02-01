<?php

namespace Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\ProjectManagementCluster;
use Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Pages\CreateProjectTask;
use Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Pages\EditProjectTask;
use Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Pages\ListProjectTasks;
use Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Schemas\ProjectTaskForm;
use Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Tables\ProjectTasksTable;
use Jmeryar\ProjectManagement\Models\ProjectTask;

class ProjectTaskResource extends Resource
{
    protected static ?string $cluster = ProjectManagementCluster::class;

    public static function getModelLabel(): string
    {
        return __('projectmanagement::project.task.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('projectmanagement::project.task.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('projectmanagement::project.task.plural_label');
    }

    protected static ?string $model = ProjectTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ProjectTaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectTasksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectTasks::route('/'),
            'create' => CreateProjectTask::route('/create'),
            'edit' => EditProjectTask::route('/{record}/edit'),
        ];
    }
}
