<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource;

class ListDunningLevels extends ListRecords
{
    protected static string $resource = DunningLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('dunning-levels'),
        ];
    }
}
