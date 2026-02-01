<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\CashAdvanceResource;

class ListCashAdvances extends ListRecords
{
    protected static string $resource = CashAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('understanding-cash-advances'),
            CreateAction::make(),
        ];
    }
}
