<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\AdjustmentDocumentResource;

class ListAdjustmentDocuments extends ListRecords
{
    protected static string $resource = AdjustmentDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Modules\Foundation\Filament\Actions\DocsAction::make('adjustment-documents'),
            \Modules\Foundation\Filament\Actions\DocsAction::make('credit-notes'),
            CreateAction::make(),
        ];
    }
}
