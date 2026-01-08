<?php

namespace Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource;

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
