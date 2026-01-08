<?php

namespace Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityCheckResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityCheckResource;

class ViewQualityCheck extends ViewRecord
{
    protected static string $resource = QualityCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // TODO: Add "Record Results" custom action
        ];
    }
}
