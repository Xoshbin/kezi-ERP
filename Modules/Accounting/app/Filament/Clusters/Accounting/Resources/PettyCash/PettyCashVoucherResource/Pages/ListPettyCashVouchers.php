<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashVoucherResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashVoucherResource;

class ListPettyCashVouchers extends ListRecords
{
    protected static string $resource = PettyCashVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Modules\Foundation\Filament\Actions\DocsAction::make('understanding-petty-cash'),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
