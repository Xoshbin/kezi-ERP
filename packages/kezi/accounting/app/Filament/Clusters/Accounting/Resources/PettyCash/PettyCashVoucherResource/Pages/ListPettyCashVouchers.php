<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashVoucherResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashVoucherResource;

class ListPettyCashVouchers extends ListRecords
{
    protected static string $resource = PettyCashVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('understanding-petty-cash'),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
