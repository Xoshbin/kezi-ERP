<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\Positions\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Kezi\Foundation\Filament\Actions\DocsAction;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Positions\PositionResource;

class ListPositions extends ListRecords
{
    protected static string $resource = PositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            DocsAction::make('department-position-config'),
        ];
    }
}
