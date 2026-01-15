<?php

namespace Modules\Foundation\Filament\Resources\NumberingSettingsResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Foundation\Filament\Actions\DocsAction;
use Modules\Foundation\Filament\Resources\NumberingSettingsResource;

class ListNumberingSettings extends ListRecords
{
    protected static string $resource = NumberingSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('understanding-numbering-settings'),
            // No create action since we can't create companies from this resource
        ];
    }
}
