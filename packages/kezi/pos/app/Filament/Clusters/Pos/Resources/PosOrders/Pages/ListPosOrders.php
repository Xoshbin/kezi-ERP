<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Resources\PosOrders\Pages;

use Filament\Resources\Pages\ListRecords;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosOrders\PosOrderResource;

class ListPosOrders extends ListRecords
{
    protected static string $resource = PosOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('pos-orders'),
        ];
    }
}
