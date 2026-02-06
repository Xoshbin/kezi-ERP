<?php

namespace Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource;

/**
 * @extends EditRecord<\Kezi\QualityControl\Models\QualityInspectionTemplate>
 */
class EditQualityInspectionTemplate extends EditRecord
{
    protected static string $resource = QualityInspectionTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
