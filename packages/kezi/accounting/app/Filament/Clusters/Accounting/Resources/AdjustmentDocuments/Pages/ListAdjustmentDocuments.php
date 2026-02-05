<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\Pages;

use \Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\AdjustmentDocumentResource;

class ListAdjustmentDocuments extends ListRecords
{
    protected static string $resource = AdjustmentDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('adjustment-documents'),
            \Kezi\Foundation\Filament\Actions\DocsAction::make('credit-notes'),
            \Kezi\Foundation\Filament\Actions\DocsAction::make('debit-notes'),
            CreateAction::make(),
        ];
    }
}
