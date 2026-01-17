<?php

namespace Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityControlPointResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityControlPointResource;

class ListQualityControlPoints extends ListRecords
{
    protected static string $resource = QualityControlPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Modules\Foundation\Filament\Actions\DocsAction::make('quality-points'),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
