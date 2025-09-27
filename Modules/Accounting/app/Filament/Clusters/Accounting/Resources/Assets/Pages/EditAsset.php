<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Assets\Pages;

use App\DataTransferObjects\Assets\UpdateAssetDTO;
use App\Filament\Clusters\Accounting\Resources\Assets\AssetResource;
use App\Services\AssetService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Asset;

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
                    return app(\Modules\Accounting\Services\AssetService::class)->delete($record);
                }),
        ];
    }

    public function computeDepreciation(): void
    {
        $asset = $this->getRecord();
        if (! $asset instanceof Asset) {
            throw new Exception('Asset not found');
        }

        app(\Modules\Accounting\Services\AssetService::class)->computeDepreciation($asset);
        Notification::make()
            ->title('Depreciation board computed')
            ->success()
            ->send();
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Asset) {
            throw new Exception('Asset not found');
        }

        $dto = new UpdateAssetDTO(...$data);

        return DB::transaction(fn () => app(\Modules\Accounting\Services\AssetService::class)->updateAsset($record, $dto));
    }
}
