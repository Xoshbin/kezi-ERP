<?php

namespace Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\WorkCenterResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\WorkCenterResource;

class ListWorkCenters extends ListRecords
{
    protected static string $resource = WorkCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Modules\Foundation\Filament\Actions\DocsAction::make('understanding-work-centers'),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
