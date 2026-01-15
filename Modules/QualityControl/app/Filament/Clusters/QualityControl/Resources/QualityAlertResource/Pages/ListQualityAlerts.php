<?php

namespace Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityAlertResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityAlertResource;

class ListQualityAlerts extends ListRecords
{
    protected static string $resource = QualityAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Modules\Foundation\Filament\Actions\DocsAction::make('quality-alerts'),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
