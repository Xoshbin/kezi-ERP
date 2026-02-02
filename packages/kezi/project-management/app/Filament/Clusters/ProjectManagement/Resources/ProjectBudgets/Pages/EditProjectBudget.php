<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\ProjectBudgetResource;

class EditProjectBudget extends EditRecord
{
    protected static string $resource = ProjectBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
