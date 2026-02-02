<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource;

class ListDunningLevels extends ListRecords
{
    protected static string $resource = DunningLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            \Kezi\Foundation\Filament\Actions\DocsAction::make('dunning-levels'),
        ];
    }
}
