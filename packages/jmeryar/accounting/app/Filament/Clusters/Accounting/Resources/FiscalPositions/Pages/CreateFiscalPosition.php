<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions\FiscalPositionResource;

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
