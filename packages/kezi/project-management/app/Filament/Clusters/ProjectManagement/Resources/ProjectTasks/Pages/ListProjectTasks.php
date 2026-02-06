<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\ProjectTaskResource;

class ListProjectTasks extends ListRecords
{
    protected static string $resource = ProjectTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            \Kezi\Foundation\Filament\Actions\DocsAction::make('understanding-project-tasks'),
        ];
    }
}
