<?php

namespace Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityControlPointResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityControlPointResource;

/**
 * @extends EditRecord<\Kezi\QualityControl\Models\QualityControlPoint>
 */
class EditQualityControlPoint extends EditRecord
{
    protected static string $resource = QualityControlPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
