<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashFundResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashFundResource;

class ListPettyCashFunds extends ListRecords
{
    protected static string $resource = PettyCashFundResource::class;
}
