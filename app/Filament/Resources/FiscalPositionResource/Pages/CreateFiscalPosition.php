<?php

namespace App\Filament\Resources\FiscalPositionResource\Pages;

use App\Filament\Resources\FiscalPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFiscalPosition extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = FiscalPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }
}
