<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashReplenishmentResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashReplenishmentResource;
use Modules\Foundation\Filament\Actions\DocsAction;

class ListPettyCashReplenishments extends ListRecords
{
    protected static string $resource = PettyCashReplenishmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('understanding-petty-cash'),
        ];
    }
}
