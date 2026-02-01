<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\ProjectManagementCluster;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\Pages\CreateProject;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\Pages\EditProject;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\Pages\ListProjects;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\Schemas\ProjectForm;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\Tables\ProjectsTable;
use Kezi\ProjectManagement\Models\Project;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = ProjectManagementCluster::class;

    public static function getModelLabel(): string
    {
        return __('projectmanagement::project.project.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('projectmanagement::project.project.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('projectmanagement::project.project.custom_navigation_label') ?? parent::getNavigationLabel();
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TasksRelationManager::class,
            RelationManagers\BudgetsRelationManager::class,
            RelationManagers\InvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
