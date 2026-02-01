<?php

namespace Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\QualityAlertResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\QualityAlertResource;

class ListQualityAlerts extends ListRecords
{
    protected static string $resource = QualityAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('quality-alerts'),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
