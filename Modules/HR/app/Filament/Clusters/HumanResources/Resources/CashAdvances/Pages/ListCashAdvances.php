<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\CashAdvanceResource;

class ListCashAdvances extends ListRecords
{
    protected static string $resource = CashAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Modules\Foundation\Filament\Actions\DocsAction::make('understanding-cash-advances'),
            CreateAction::make(),
        ];
    }
}
