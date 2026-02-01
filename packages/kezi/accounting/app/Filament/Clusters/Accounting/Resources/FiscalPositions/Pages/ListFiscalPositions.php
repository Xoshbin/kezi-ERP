<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions\FiscalPositionResource;
use Kezi\Foundation\Filament\Actions\DocsAction;

class ListFiscalPositions extends ListRecords
{
    use Translatable;

    protected static string $resource = FiscalPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DocsAction::make('fiscal-positions'),
            CreateAction::make(),
        ];
    }
}
