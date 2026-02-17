<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Resources\PosSessions\Pages;

use Filament\Resources\Pages\ListRecords;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosSessions\PosSessionResource;

class ListPosSessions extends ListRecords
{
    protected static string $resource = PosSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action as sessions are managed via POS terminal
        ];
    }
}
