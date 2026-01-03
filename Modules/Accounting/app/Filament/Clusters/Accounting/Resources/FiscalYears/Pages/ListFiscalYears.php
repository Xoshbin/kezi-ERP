<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalYears\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalYears\FiscalYearResource;

class ListFiscalYears extends ListRecords
{
    protected static string $resource = FiscalYearResource::class;
}
