<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveTypes\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveTypes\LeaveTypeResource;

class EditLeaveType extends EditRecord
{
    use Translatable;

    protected static string $resource = LeaveTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }
}
