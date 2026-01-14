<?php

namespace Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityAlertResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityAlertResource;

class EditQualityAlert extends EditRecord
{
    protected static string $resource = QualityAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
