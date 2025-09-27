<?php

namespace Modules\Accounting\Services;

use App\Actions\Assets\CreateAssetAction;
use App\Actions\Assets\DisposeAssetAction;
use App\Actions\Assets\PostDepreciationEntryAction;
use App\Actions\Assets\UpdateAssetAction;
use App\DataTransferObjects\Assets\CreateAssetDTO;
use App\DataTransferObjects\Assets\DisposeAssetDTO;
use App\DataTransferObjects\Assets\UpdateAssetDTO;
use App\Enums\Assets\AssetStatus;
use App\Enums\Assets\DepreciationEntryStatus;
use App\Exceptions\DeletionNotAllowedException;
use App\Models\Asset;
use App\Models\DepreciationEntry;
use App\Models\User;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AssetService
{
    public function __construct(
        protected CreateAssetAction $createAssetAction,
        protected UpdateAssetAction $updateAssetAction,
        protected DisposeAssetAction $disposeAssetAction,
        protected PostDepreciationEntryAction $postDepreciationEntryAction
    ) {}

    public function createAsset(\Modules\Accounting\DataTransferObjects\Assets\CreateAssetDTO $dto): \Modules\Accounting\Models\Asset
    {
        return $this->createAssetAction->execute($dto);
    }

    public function updateAsset(\Modules\Accounting\Models\Asset $asset, UpdateAssetDTO $dto): \Modules\Accounting\Models\Asset
    {
        return $this->updateAssetAction->execute($asset, $dto);
    }

    public function disposeAsset(\Modules\Accounting\Models\Asset $asset, DisposeAssetDTO $dto, User $user): \Modules\Accounting\Models\Asset
    {
        return $this->disposeAssetAction->execute($asset, $dto, $user);
    }

    public function postDepreciation(\Modules\Accounting\Models\DepreciationEntry $depreciationEntry, User $user): \Modules\Accounting\Models\DepreciationEntry
    {
        return $this->postDepreciationEntryAction->execute($depreciationEntry, $user);
    }

    /**
     * Delete an asset, but only if it is in draft status and has no associated financial records.
     * Enforces the accounting principle of immutability for confirmed assets and those with financial history.
     *
     * @param \Modules\Accounting\Models\Asset $asset The asset to be deleted.
     * @return bool True on successful deletion.
     *
     * @throws \Modules\Foundation\Exceptions\DeletionNotAllowedException If the asset cannot be deleted due to business rules.
     */
    public function delete(\Modules\Accounting\Models\Asset $asset): bool
    {
        // Guard Clause 1: Only allow deleting if the status is Draft.
        if ($asset->status !== AssetStatus::Draft) {
            throw new \Modules\Foundation\Exceptions\DeletionNotAllowedException(
                'Cannot delete a confirmed asset. Only draft assets can be deleted.'
            );
        }

        // Guard Clause 2: Check for any depreciation entries (even draft ones).
        if ($asset->depreciationEntries()->exists()) {
            throw new \Modules\Foundation\Exceptions\DeletionNotAllowedException(
                'Cannot delete an asset with depreciation entries. Depreciation history must be preserved.'
            );
        }

        // Guard Clause 3: Check for any journal entries.
        if ($asset->journalEntries()->exists()) {
            throw new \Modules\Foundation\Exceptions\DeletionNotAllowedException(
                'Cannot delete an asset with associated journal entries. Financial records must be preserved.'
            );
        }

        // If all guards pass, proceed with the deletion.
        $result = $asset->delete();

        return $result !== null ? $result : false;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function computeDepreciation(\Modules\Accounting\Models\Asset $asset): Collection
    {
        $asset->load('currency');
        $depreciableValue = $asset->purchase_value->minus($asset->salvage_value, RoundingMode::HALF_UP);

        $monthlyDepreciation = $depreciableValue->dividedBy($asset->useful_life_years * 12, RoundingMode::HALF_UP);

        $depreciationDate = Carbon::parse($asset->purchase_date)->startOfMonth();

        $entries = collect();

        for ($i = 0; $i < $asset->useful_life_years * 12; $i++) {
            $depreciationDate = $depreciationDate->addMonth();
            $entry = \Modules\Accounting\Models\DepreciationEntry::create([
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
