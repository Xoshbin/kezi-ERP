<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\ProjectTaskResource;

/**
 * @extends EditRecord<\Kezi\ProjectManagement\Models\ProjectTask>
 */
class EditProjectTask extends EditRecord
{
    protected static string $resource = ProjectTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
