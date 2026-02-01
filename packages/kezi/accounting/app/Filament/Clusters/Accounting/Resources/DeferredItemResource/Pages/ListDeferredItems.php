<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\DeferredItemResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\DeferredItemResource;

class ListDeferredItems extends ListRecords
{
    protected static string $resource = DeferredItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('deferred-items'),
            Actions\CreateAction::make(),
        ];
    }
}
