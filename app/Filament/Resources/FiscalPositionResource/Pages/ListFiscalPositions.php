<?php

namespace App\Filament\Resources\FiscalPositionResource\Pages;

use App\Filament\Resources\FiscalPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFiscalPositions extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = FiscalPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}
