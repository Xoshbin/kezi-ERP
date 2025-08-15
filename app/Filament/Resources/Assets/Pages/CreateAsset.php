<?php

namespace App\Filament\Resources\Assets\Pages;

use Illuminate\Database\Eloquent\Model;
use App\DataTransferObjects\Assets\CreateAssetDTO;
use App\Filament\Resources\Assets\AssetResource;
use App\Services\AssetService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $dto = new CreateAssetDTO(...$data);

        return DB::transaction(fn () => app(AssetService::class)->createAsset($dto));
    }
}
