<?php

namespace Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource;

/**
 * @extends EditRecord<\Kezi\QualityControl\Models\DefectType>
 */
class EditDefectType extends EditRecord
{
    protected static string $resource = DefectTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
