<?php

namespace App\Filament\Resources\AssetResource\Pages;

use App\DataTransferObjects\Assets\UpdateAssetDTO;
use App\Filament\Resources\AssetResource;
use App\Services\AssetService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditAsset extends EditRecord
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('computeDepreciation')
                ->label('Compute Depreciation Board')
                ->action('computeDepreciation'),
            Actions\DeleteAction::make()
                ->using(function ($record) {
                    // Use the AssetService to handle deletion with proper business logic
                    return app(AssetService::class)->delete($record);
                }),
        ];
    }

    public function computeDepreciation(): void
    {
        app(AssetService::class)->computeDepreciation($this->getRecord());
        $this->notify('success', 'Depreciation board computed.');
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $dto = new UpdateAssetDTO(...$data);

        return DB::transaction(fn () => app(AssetService::class)->updateAsset($record, $dto));
    }
}
