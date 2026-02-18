<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Resources\PosProfiles\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosProfiles\PosProfileResource;

class ListPosProfiles extends ListRecords
{
    protected static string $resource = PosProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('pos-profiles'),
            Actions\CreateAction::make(),
        ];
    }
}
