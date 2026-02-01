<?php

namespace Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\WorkCenterResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\WorkCenterResource;

class ListWorkCenters extends ListRecords
{
    protected static string $resource = WorkCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('understanding-work-centers'),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
