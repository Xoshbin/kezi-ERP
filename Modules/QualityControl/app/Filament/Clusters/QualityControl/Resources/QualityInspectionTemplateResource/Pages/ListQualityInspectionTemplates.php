<?php

namespace Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource;

class ListQualityInspectionTemplates extends ListRecords
{
    protected static string $resource = QualityInspectionTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
