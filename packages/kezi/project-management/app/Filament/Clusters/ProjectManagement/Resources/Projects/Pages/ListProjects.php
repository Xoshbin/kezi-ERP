<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\ProjectResource;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('project-management'),
            CreateAction::make(),
        ];
    }
}
