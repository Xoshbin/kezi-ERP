<?php

namespace App\Filament\Resources\AdjustmentDocuments\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\AdjustmentDocuments\AdjustmentDocumentResource;
use Filament\Actions;
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
