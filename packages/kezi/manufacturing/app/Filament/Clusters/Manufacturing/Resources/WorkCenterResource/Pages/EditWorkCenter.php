<?php

namespace Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\WorkCenterResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\WorkCenterResource;

/**
 * @extends EditRecord<\Kezi\Manufacturing\Models\WorkCenter>
 */
class EditWorkCenter extends EditRecord
{
    protected static string $resource = WorkCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
