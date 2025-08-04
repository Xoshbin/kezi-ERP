<?php

namespace App\Filament\Resources\AssetResource\Pages;

use App\DataTransferObjects\Assets\CreateAssetDTO;
use App\Filament\Resources\AssetResource;
use App\Services\AssetService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $dto = new CreateAssetDTO(...$data);

        return DB::transaction(fn () => app(AssetService::class)->createAsset($dto));
    }
}
