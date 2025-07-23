<?php

namespace App\Filament\Resources\FiscalPositionResource\Pages;

use App\Filament\Resources\FiscalPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFiscalPositions extends ListRecords
{
    protected static string $resource = FiscalPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
