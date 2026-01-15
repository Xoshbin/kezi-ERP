<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Assets\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Assets\AssetResource;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Modules\Foundation\Filament\Actions\DocsAction::make('fixed-assets'),
            CreateAction::make(),
        ];
    }
}
