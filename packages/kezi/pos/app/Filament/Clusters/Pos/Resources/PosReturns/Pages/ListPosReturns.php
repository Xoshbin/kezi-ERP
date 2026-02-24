<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Resources\PosReturns\Pages;

use Filament\Resources\Pages\ListRecords;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosReturns\PosReturnResource;

class ListPosReturns extends ListRecords
{
    protected static string $resource = PosReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('pos-returns'),
        ];
    }
}
