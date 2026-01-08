<?php

namespace Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\ProjectTaskResource;

class ListProjectTasks extends ListRecords
{
    protected static string $resource = ProjectTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
