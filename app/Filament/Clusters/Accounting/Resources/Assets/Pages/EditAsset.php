<?php

namespace App\Filament\Clusters\Accounting\Resources\Assets\Pages;

use App\DataTransferObjects\Assets\UpdateAssetDTO;
use App\Filament\Clusters\Accounting\Resources\Assets\AssetResource;
use App\Services\AssetService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditAsset extends EditRecord
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('computeDepreciation')
                ->label('Compute Depreciation Board')
                ->action('computeDepreciation'),
            DeleteAction::make()
                ->using(function ($record) {
                    // Use the AssetService to handle deletion with proper business logic
                    return app(AssetService::class)->delete($record);
                }),
        ];
    }

    public function computeDepreciation(): void
    {
        app(AssetService::class)->computeDepreciation($this->getRecord());
        \Filament\Notifications\Notification::make()
            ->title('Depreciation board computed')
            ->success()
            ->send();
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $dto = new UpdateAssetDTO(...$data);

        return DB::transaction(fn () => app(AssetService::class)->updateAsset($record, $dto));
    }
}
