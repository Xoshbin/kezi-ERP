<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequebookResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequebookResource;
use Jmeryar\Foundation\Filament\Actions\DocsAction;

class ListChequebooks extends ListRecords
{
    protected static string $resource = ChequebookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('cheque-management'),
            Actions\CreateAction::make(),
        ];
    }
}
