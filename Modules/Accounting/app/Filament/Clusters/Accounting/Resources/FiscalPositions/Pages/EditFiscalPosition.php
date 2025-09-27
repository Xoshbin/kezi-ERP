<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions\Pages;

use App\Filament\Clusters\Accounting\Resources\FiscalPositions\FiscalPositionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
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
