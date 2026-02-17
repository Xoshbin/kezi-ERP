<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Resources\PosProfiles\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosProfiles\PosProfileResource;

class CreatePosProfile extends CreateRecord
{
    protected static string $resource = PosProfileResource::class;
}
