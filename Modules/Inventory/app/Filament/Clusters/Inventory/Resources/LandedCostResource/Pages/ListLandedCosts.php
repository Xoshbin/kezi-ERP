<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\LandedCostResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\LandedCostResource;

class ListLandedCosts extends ListRecords
{
    protected static string $resource = LandedCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
