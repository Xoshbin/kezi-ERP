<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Assets\Pages;

use Carbon\Carbon;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\DataTransferObjects\Assets\UpdateAssetDTO;
use Kezi\Accounting\Enums\Assets\DepreciationMethod;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Assets\AssetResource;
use Kezi\Accounting\Models\Asset;

/**
 * @extends EditRecord<\Kezi\Accounting\Models\Asset>
 */
class EditAsset extends EditRecord
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('compute_depreciation_board')
                ->label(__('accounting::asset.compute_depreciation_board'))
                ->action(fn () => $this->computeDepreciation()),
            DeleteAction::make()
                ->using(function ($record) {
                    // Use the AssetService to handle deletion with proper business logic
                    return app(\Kezi\Accounting\Services\AssetService::class)->delete($record);
                }),
        ];
    }

    public function computeDepreciation(): void
    {
        $asset = $this->getRecord();
        if (! $asset instanceof Asset) {
            throw new Exception('Asset not found');
        }

        app(\Kezi\Accounting\Services\AssetService::class)->computeDepreciation($asset);
        Notification::make()
            ->title(__('accounting::asset.depreciation_board_computed'))
            ->success()
            ->send();
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Asset) {
            throw new Exception('Asset not found');
        }

        $currency = \Kezi\Foundation\Models\Currency::find($data['currency_id']);
        if (! $currency) {
            throw new Exception('Currency not found');
        }

        // Convert purchase_date string to Carbon instance if needed
        if (isset($data['purchase_date']) && is_string($data['purchase_date'])) {
            $data['purchase_date'] = Carbon::parse($data['purchase_date']);
        }

        // Convert depreciation_method string to DepreciationMethod enum if needed
        if (isset($data['depreciation_method']) && is_string($data['depreciation_method'])) {
            $data['depreciation_method'] = DepreciationMethod::from($data['depreciation_method']);
        }

        // Convert Money fields from major units (string) to minor units (int)
        if (isset($data['purchase_value'])) {
            $data['purchase_value'] = \Brick\Money\Money::of($data['purchase_value'], $currency->code, null, \Brick\Math\RoundingMode::HALF_UP)
                ->getMinorAmount()
                ->toInt();
        }

        if (isset($data['salvage_value'])) {
            $data['salvage_value'] = \Brick\Money\Money::of($data['salvage_value'], $currency->code, null, \Brick\Math\RoundingMode::HALF_UP)
                ->getMinorAmount()
                ->toInt();
        }

        // Filter data to only valid DTO properties to avoid "Unknown named parameter" error
        $reflection = new \ReflectionClass(UpdateAssetDTO::class);
        $constructor = $reflection->getConstructor();
        $validParams = array_map(fn ($p) => $p->getName(), $constructor->getParameters());

        $dtoData = array_intersect_key($data, array_flip($validParams));

        $dto = new UpdateAssetDTO(...$dtoData);

        return DB::transaction(fn () => app(\Kezi\Accounting\Services\AssetService::class)->updateAsset($record, $dto));
    }
}
