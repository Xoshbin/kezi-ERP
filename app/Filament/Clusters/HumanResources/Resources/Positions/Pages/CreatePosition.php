<?php

namespace App\Filament\Clusters\HumanResources\Resources\Positions\Pages;

use App\Filament\Clusters\HumanResources\Resources\Positions\PositionResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePosition extends CreateRecord
{
    protected static string $resource = PositionResource::class;
}
