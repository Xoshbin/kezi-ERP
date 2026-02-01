<?php

namespace Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityAlertResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityAlertResource;

class ListQualityAlerts extends ListRecords
{
    protected static string $resource = QualityAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('quality-alerts'),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
