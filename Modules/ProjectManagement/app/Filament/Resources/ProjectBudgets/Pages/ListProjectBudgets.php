<?php

namespace Modules\ProjectManagement\Filament\Resources\ProjectBudgets\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\ProjectManagement\Filament\Resources\ProjectBudgets\ProjectBudgetResource;

class ListProjectBudgets extends ListRecords
{
    protected static string $resource = ProjectBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
