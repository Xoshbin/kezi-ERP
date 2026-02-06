<?php

namespace Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityCheckResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityCheckResource;

/**
 * @extends ViewRecord<\Kezi\QualityControl\Models\QualityCheck>
 */
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
