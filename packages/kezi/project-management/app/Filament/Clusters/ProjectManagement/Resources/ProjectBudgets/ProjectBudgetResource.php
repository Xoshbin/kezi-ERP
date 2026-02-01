<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\ProjectManagementCluster;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\Pages\CreateProjectBudget;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\Pages\EditProjectBudget;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\Pages\ListProjectBudgets;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\Schemas\ProjectBudgetForm;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\Tables\ProjectBudgetsTable;
use Kezi\ProjectManagement\Models\ProjectBudget;

class ProjectBudgetResource extends Resource
{
    protected static ?string $cluster = ProjectManagementCluster::class;

    protected static ?string $model = ProjectBudget::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('projectmanagement::project.budget.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('projectmanagement::project.budget.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectBudgetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectBudgetsTable::configure($table);
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
            'index' => ListProjectBudgets::route('/'),
            'create' => CreateProjectBudget::route('/create'),
            'edit' => EditProjectBudget::route('/{record}/edit'),
        ];
    }
}
