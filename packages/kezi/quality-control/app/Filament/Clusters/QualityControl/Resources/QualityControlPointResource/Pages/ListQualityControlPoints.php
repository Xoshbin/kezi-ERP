<?php

namespace Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityControlPointResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityControlPointResource;

class ListQualityControlPoints extends ListRecords
{
    protected static string $resource = QualityControlPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('quality-points'),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
