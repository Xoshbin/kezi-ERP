<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Assets\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Assets\AssetResource;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $currency = \Modules\Foundation\Models\Currency::find($data['currency_id']);
        if (! $currency) {
             throw new \Exception("Currency not found");
        }
        
        // Convert Money fields from major units (string) to minor units (int)
        if (isset($data['purchase_value'])) {
             // MoneyInput returns major units as string. Convert to minor units.
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
        $reflection = new \ReflectionClass(\Modules\Accounting\DataTransferObjects\Assets\CreateAssetDTO::class);
        $constructor = $reflection->getConstructor();
        $validParams = array_map(fn ($p) => $p->getName(), $constructor->getParameters());
        
        $dtoData = array_intersect_key($data, array_flip($validParams));
        
        // Ensure defaults for optional fields if missing
        if (! isset($dtoData['source_type'])) $dtoData['source_type'] = null;
        if (! isset($dtoData['source_id'])) $dtoData['source_id'] = null;

        $dto = new \Modules\Accounting\DataTransferObjects\Assets\CreateAssetDTO(...$dtoData);

        return DB::transaction(fn () => app(\Modules\Accounting\Services\AssetService::class)->createAsset($dto));
    }
}
