<?php

namespace App\Filament\Resources\FiscalPositions\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use App\Filament\Resources\FiscalPositions\FiscalPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFiscalPosition extends CreateRecord
{
    use Translatable;

    protected static string $resource = FiscalPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
