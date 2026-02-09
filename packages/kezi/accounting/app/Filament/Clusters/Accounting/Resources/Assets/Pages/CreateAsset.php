<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Assets\Pages;

use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Enums\Assets\DepreciationMethod;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Assets\AssetResource;

/**
 * @extends CreateRecord<\Kezi\Accounting\Models\Asset>
 */
class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $currency = \Kezi\Foundation\Models\Currency::find($data['currency_id']);
        if (! $currency) {
            throw new \Exception('Currency not found');
        }

        // Convert purchase_date string to Carbon instance if needed
        if (isset($data['purchase_date']) && is_string($data['purchase_date'])) {
            $data['purchase_date'] = Carbon::parse($data['purchase_date']);
        }

        // Convert depreciation_method string to DepreciationMethod enum if needed
        if (isset($data['depreciation_method']) && is_string($data['depreciation_method'])) {
            $data['depreciation_method'] = DepreciationMethod::from($data['depreciation_method']);
        }

        // Convert Money fields from major units (string)
        if (isset($data['purchase_value'])) {
            $data['purchase_value'] = \Brick\Money\Money::of($data['purchase_value'], $currency->code, null, \Brick\Math\RoundingMode::HALF_UP);
        }

        if (isset($data['salvage_value'])) {
            $data['salvage_value'] = \Brick\Money\Money::of($data['salvage_value'], $currency->code, null, \Brick\Math\RoundingMode::HALF_UP);
        }

        // Filter data to only valid DTO properties to avoid "Unknown named parameter" error
        $reflection = new \ReflectionClass(\Kezi\Accounting\DataTransferObjects\Assets\CreateAssetDTO::class);
        $constructor = $reflection->getConstructor();
        $validParams = array_map(fn ($p) => $p->getName(), $constructor->getParameters());

        $dtoData = array_intersect_key($data, array_flip($validParams));

        // Ensure defaults for optional fields if missing
        if (! isset($dtoData['source_type'])) {
            $dtoData['source_type'] = null;
        }
        if (! isset($dtoData['source_id'])) {
            $dtoData['source_id'] = null;
        }

        $dto = new \Kezi\Accounting\DataTransferObjects\Assets\CreateAssetDTO(...$dtoData);

        return DB::transaction(fn () => app(\Kezi\Accounting\Services\AssetService::class)->createAsset($dto));
    }
}
