<?php

namespace Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityCheckResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityCheckResource;

class ListQualityChecks extends ListRecords
{
    protected static string $resource = QualityCheckResource::class;
}
