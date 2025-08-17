<?php

namespace App\Filament\Resources\FiscalPositions\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Filament\Actions\CreateAction;
use App\Filament\Resources\FiscalPositions\FiscalPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFiscalPositions extends ListRecords
{
    use Translatable;

    protected static string $resource = FiscalPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            CreateAction::make(),
        ];
    }
}
