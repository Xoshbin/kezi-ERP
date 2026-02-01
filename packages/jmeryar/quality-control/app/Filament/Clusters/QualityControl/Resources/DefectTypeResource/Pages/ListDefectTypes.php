<?php

namespace Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Jmeryar\Foundation\Filament\Actions\DocsAction;
use Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource;

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
