<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions\FiscalPositionResource;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditFiscalPosition extends EditRecord
{
    use Translatable;

    protected static string $resource = FiscalPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }
}
