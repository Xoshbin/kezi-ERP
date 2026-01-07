<?php

namespace Modules\ProjectManagement\Filament\Resources\ProjectTasks;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\ProjectManagement\Filament\Resources\ProjectTasks\Pages\CreateProjectTask;
use Modules\ProjectManagement\Filament\Resources\ProjectTasks\Pages\EditProjectTask;
use Modules\ProjectManagement\Filament\Resources\ProjectTasks\Pages\ListProjectTasks;
use Modules\ProjectManagement\Filament\Resources\ProjectTasks\Schemas\ProjectTaskForm;
use Modules\ProjectManagement\Filament\Resources\ProjectTasks\Tables\ProjectTasksTable;
use Modules\ProjectManagement\Models\ProjectTask;

class ProjectTaskResource extends Resource
{
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
