<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequeResource;

class ListCheques extends ListRecords
{
    protected static string $resource = ChequeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('cheque-management'),
        ];
    }
}
