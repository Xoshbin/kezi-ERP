<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\Positions\Pages;

use \Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Positions\PositionResource;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

/**
 * @extends EditRecord<\Kezi\HR\Models\Position>
 */
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
