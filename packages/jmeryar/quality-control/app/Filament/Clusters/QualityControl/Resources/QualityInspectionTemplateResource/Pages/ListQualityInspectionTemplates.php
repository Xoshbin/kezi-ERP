<?php

namespace Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Jmeryar\Foundation\Filament\Actions\DocsAction;
use Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource;

class ListQualityInspectionTemplates extends ListRecords
{
    protected static string $resource = QualityInspectionTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('quality-checks'),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
