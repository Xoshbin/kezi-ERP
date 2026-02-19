<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Resources\PosProfiles\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosProfiles\PosProfileResource;

class EditPosProfile extends EditRecord
{
    protected static string $resource = PosProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
