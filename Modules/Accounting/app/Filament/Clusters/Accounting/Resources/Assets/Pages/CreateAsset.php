<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Assets\Pages;

use App\DataTransferObjects\Assets\CreateAssetDTO;
use App\Filament\Clusters\Accounting\Resources\Assets\AssetResource;
use App\Services\AssetService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $dto = new \Modules\Accounting\DataTransferObjects\Assets\CreateAssetDTO(...$data);

        return DB::transaction(fn () => app(\Modules\Accounting\Services\AssetService::class)->createAsset($dto));
    }
}
