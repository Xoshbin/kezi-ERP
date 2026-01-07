<?php

namespace Modules\ProjectManagement\Filament\Resources\ProjectBudgets\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\ProjectManagement\Filament\Resources\ProjectBudgets\ProjectBudgetResource;

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
