<?php

namespace Modules\Accounting\Filament\Resources\DeferredItemResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Resources\DeferredItemResource;

class ListDeferredItems extends ListRecords
{
    protected static string $resource = DeferredItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
