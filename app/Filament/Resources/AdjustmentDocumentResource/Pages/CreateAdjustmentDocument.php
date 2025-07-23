<?php

namespace App\Filament\Resources\AdjustmentDocumentResource\Pages;

use App\Filament\Resources\AdjustmentDocumentResource;
use App\Services\AdjustmentDocumentService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAdjustmentDocument extends CreateRecord
{
    protected static string $resource = AdjustmentDocumentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return static::getModel()::create($data);
    }
}
