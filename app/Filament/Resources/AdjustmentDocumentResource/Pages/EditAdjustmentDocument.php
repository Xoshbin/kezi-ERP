<?php

namespace App\Filament\Resources\AdjustmentDocumentResource\Pages;

use App\Filament\Resources\AdjustmentDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdjustmentDocument extends EditRecord
{
    protected static string $resource = AdjustmentDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
