<?php

namespace Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\ProjectTaskResource;

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
