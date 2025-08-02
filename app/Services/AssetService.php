<?php

namespace App\Services;

use App\Actions\Assets\CreateAssetAction;
use App\Actions\Assets\DisposeAssetAction;
use App\Actions\Assets\PostDepreciationEntryAction;
use App\Actions\Assets\UpdateAssetAction;
use App\DataTransferObjects\Assets\CreateAssetDTO;
use App\DataTransferObjects\Assets\DisposeAssetDTO;
use App\DataTransferObjects\Assets\UpdateAssetDTO;
use App\Enums\Assets\DepreciationEntryStatus;
use App\Models\Asset;
use App\Models\DepreciationEntry;
use App\Models\User;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AssetService
{
    public function __construct(
        protected CreateAssetAction $createAssetAction,
        protected UpdateAssetAction $updateAssetAction,
        protected DisposeAssetAction $disposeAssetAction,
        protected PostDepreciationEntryAction $postDepreciationEntryAction
    ) {
    }

    public function createAsset(CreateAssetDTO $dto): Asset
    {
        return $this->createAssetAction->execute($dto);
    }

    public function updateAsset(Asset $asset, UpdateAssetDTO $dto): Asset
    {
        return $this->updateAssetAction->execute($asset, $dto);
    }

    public function disposeAsset(Asset $asset, DisposeAssetDTO $dto): Asset
    {
        return $this->disposeAssetAction->execute($asset, $dto);
    }

    public function postDepreciation(DepreciationEntry $depreciationEntry, User $user): DepreciationEntry
    {
        return $this->postDepreciationEntryAction->execute($depreciationEntry, $user);
    }

    public function computeDepreciation(Asset $asset): Collection
    {
        $asset->load('currency');
        $depreciableValue = $asset->purchase_price->minus($asset->salvage_value, RoundingMode::HALF_UP);

        $monthlyDepreciation = $depreciableValue->dividedBy($asset->useful_life * 12, RoundingMode::HALF_UP);

        $depreciationDate = Carbon::parse($asset->purchase_date)->startOfMonth();

        $entries = collect();

        for ($i = 0; $i < $asset->useful_life * 12; $i++) {
            $depreciationDate = $depreciationDate->addMonth();
            $entry = DepreciationEntry::create([
                'asset_id' => $asset->id,
                'company_id' => $asset->company_id,
                'amount' => $monthlyDepreciation,
                'depreciation_date' => $depreciationDate,
                'status' => DepreciationEntryStatus::Draft,
            ]);
            $entries->push($entry);
        }

        return $entries;
    }
}
