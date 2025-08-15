<?php

namespace App\Filament\Resources\Companies\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
