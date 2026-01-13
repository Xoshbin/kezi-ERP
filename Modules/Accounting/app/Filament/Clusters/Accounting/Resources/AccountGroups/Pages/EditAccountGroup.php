<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\AccountGroups\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AccountGroups\AccountGroupResource;

class EditAccountGroup extends EditRecord
{
    use Translatable;

    protected static string $resource = AccountGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }
}
