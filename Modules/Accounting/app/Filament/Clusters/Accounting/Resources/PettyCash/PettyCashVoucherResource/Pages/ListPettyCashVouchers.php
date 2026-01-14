<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashVoucherResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashVoucherResource;

class ListPettyCashVouchers extends ListRecords
{
    protected static string $resource = PettyCashVoucherResource::class;
}
