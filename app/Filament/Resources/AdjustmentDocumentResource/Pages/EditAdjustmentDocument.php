<?php

namespace App\Filament\Resources\AdjustmentDocumentResource\Pages;

use App\Filament\Resources\AdjustmentDocumentResource;
use App\Services\AdjustmentDocumentService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAdjustmentDocument extends EditRecord
{
    protected static string $resource = AdjustmentDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $adjustmentDocumentService = new AdjustmentDocumentService();
        $adjustmentDocumentService->update($record, $data);
        return $record;
    }
}
