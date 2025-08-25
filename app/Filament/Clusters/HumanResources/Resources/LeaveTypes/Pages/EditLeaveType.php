<?php

namespace App\Filament\Clusters\HumanResources\Resources\LeaveTypes\Pages;

use App\Filament\Clusters\HumanResources\Resources\LeaveTypes\LeaveTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;

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
