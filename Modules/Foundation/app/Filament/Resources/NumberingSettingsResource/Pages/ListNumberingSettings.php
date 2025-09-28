<?php

namespace Modules\Foundation\Filament\Clusters\Settings\Resources\NumberingSettingsResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Foundation\Filament\Clusters\Settings\Resources\NumberingSettingsResource;

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
