<?php

namespace App\Filament\Resources\FiscalPositions\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\FiscalPositions\FiscalPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
