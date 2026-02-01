<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\AdjustmentDocumentResource;

class ListAdjustmentDocuments extends ListRecords
{
    protected static string $resource = AdjustmentDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('adjustment-documents'),
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('credit-notes'),
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('debit-notes'),
            CreateAction::make(),
        ];
    }
}
