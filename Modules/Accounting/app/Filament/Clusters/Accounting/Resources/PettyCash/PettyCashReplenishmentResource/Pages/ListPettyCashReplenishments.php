<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashReplenishmentResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashReplenishmentResource;

class ListPettyCashReplenishments extends ListRecords
{
    protected static string $resource = PettyCashReplenishmentResource::class;
}
