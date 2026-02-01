<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashFundResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashFundResource;
use Kezi\Foundation\Filament\Actions\DocsAction;

class ListPettyCashFunds extends ListRecords
{
    protected static string $resource = PettyCashFundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('understanding-petty-cash'),
        ];
    }
}
