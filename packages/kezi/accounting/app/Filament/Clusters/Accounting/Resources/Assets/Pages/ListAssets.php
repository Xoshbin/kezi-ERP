<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Assets\Pages;

use \Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Assets\AssetResource;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('fixed-assets'),
            CreateAction::make(),
        ];
    }
}
