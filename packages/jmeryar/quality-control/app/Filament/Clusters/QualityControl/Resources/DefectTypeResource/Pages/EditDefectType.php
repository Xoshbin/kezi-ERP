<?php

namespace Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource;

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
