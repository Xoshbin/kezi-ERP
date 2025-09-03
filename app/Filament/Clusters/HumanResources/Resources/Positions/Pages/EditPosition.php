<?php

namespace App\Filament\Clusters\HumanResources\Resources\Positions\Pages;

use App\Filament\Clusters\HumanResources\Resources\Positions\PositionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditPosition extends EditRecord
{
    use Translatable;

    protected static string $resource = PositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }
}
