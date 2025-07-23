<?php

namespace App\Filament\Resources\AdjustmentDocumentResource\Pages;

use App\Filament\Resources\AdjustmentDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdjustmentDocuments extends ListRecords
{
    protected static string $resource = AdjustmentDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
