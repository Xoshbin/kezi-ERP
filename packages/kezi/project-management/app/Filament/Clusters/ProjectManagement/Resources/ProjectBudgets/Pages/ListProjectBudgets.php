<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\ProjectBudgetResource;

class ListProjectBudgets extends ListRecords
{
    protected static string $resource = ProjectBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('project-budgeting'),
            CreateAction::make(),
        ];
    }
}
