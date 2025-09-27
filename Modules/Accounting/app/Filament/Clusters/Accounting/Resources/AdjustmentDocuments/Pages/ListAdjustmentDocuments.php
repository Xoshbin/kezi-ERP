<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\Pages;

use App\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\AdjustmentDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdjustmentDocuments extends ListRecords
{
    protected static string $resource = AdjustmentDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
