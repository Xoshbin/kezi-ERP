<?php

namespace App\Filament\Clusters\HumanResources\Resources\LeaveTypes\Pages;

use App\Filament\Clusters\HumanResources\Resources\LeaveTypes\LeaveTypeResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateLeaveType extends CreateRecord
{
    use Translatable;

    protected static string $resource = LeaveTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
