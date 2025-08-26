<?php

namespace App\Filament\Clusters\Settings\Resources\NumberingSettingsResource\Pages;

use App\Filament\Clusters\Settings\Resources\NumberingSettingsResource;
use Filament\Resources\Pages\ListRecords;

class ListNumberingSettings extends ListRecords
{
    protected static string $resource = NumberingSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action since we can't create companies from this resource
        ];
    }
}
