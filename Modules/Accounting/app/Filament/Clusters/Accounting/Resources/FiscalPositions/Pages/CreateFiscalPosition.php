<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions\Pages;

use App\Filament\Clusters\Accounting\Resources\FiscalPositions\FiscalPositionResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

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
