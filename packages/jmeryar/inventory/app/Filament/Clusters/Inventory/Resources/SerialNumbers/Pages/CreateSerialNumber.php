<?php

namespace Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\SerialNumbers\Pages;

use Filament\Resources\Pages\CreateRecord;
use Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\SerialNumbers\SerialNumberResource;

class CreateSerialNumber extends CreateRecord
{
    protected static string $resource = SerialNumberResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->getKey();

        return $data;
    }
}
