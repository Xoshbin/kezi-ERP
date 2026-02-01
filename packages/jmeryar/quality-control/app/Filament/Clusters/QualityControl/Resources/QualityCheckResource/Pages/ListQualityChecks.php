<?php

namespace Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\QualityCheckResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\QualityCheckResource;

class ListQualityChecks extends ListRecords
{
    protected static string $resource = QualityCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('quality-checks'),
        ];
    }
}
