<?php

namespace Modules\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Foundation\Filament\Actions\DocsAction;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource;

class ListDefectTypes extends ListRecords
{
    protected static string $resource = DefectTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('quality-checks'),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
