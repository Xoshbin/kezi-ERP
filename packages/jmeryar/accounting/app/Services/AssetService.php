<?php

namespace Jmeryar\Accounting\Services;

use App\Models\User;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Jmeryar\Accounting\Actions\Assets\CreateAssetAction;
use Jmeryar\Accounting\Actions\Assets\DisposeAssetAction;
use Jmeryar\Accounting\Actions\Assets\PostDepreciationEntryAction;
use Jmeryar\Accounting\Actions\Assets\UpdateAssetAction;
use Jmeryar\Accounting\DataTransferObjects\Assets\DisposeAssetDTO;
use Jmeryar\Accounting\DataTransferObjects\Assets\UpdateAssetDTO;
use Jmeryar\Accounting\Enums\Assets\AssetStatus;
use Jmeryar\Accounting\Enums\Assets\DepreciationEntryStatus;
use Jmeryar\Accounting\Models\Asset;
use Jmeryar\Accounting\Models\DepreciationEntry;

class AssetService
{
    public function __construct(
        protected CreateAssetAction $createAssetAction,
        protected UpdateAssetAction $updateAssetAction,
        protected DisposeAssetAction $disposeAssetAction,
        protected PostDepreciationEntryAction $postDepreciationEntryAction,
    ) {}

    public function createAsset(\Jmeryar\Accounting\DataTransferObjects\Assets\CreateAssetDTO $dto): Asset
    {
        return $this->createAssetAction->execute($dto);
    }

    public function updateAsset(Asset $asset, UpdateAssetDTO $dto): Asset
    {
        return $this->updateAssetAction->execute($asset, $dto);
    }

    public function disposeAsset(Asset $asset, DisposeAssetDTO $dto, User $user): Asset
    {
        return $this->disposeAssetAction->execute($asset, $dto, $user);
    }

    public function postDepreciation(DepreciationEntry $depreciationEntry, User $user): DepreciationEntry
    {
        return $this->postDepreciationEntryAction->execute($depreciationEntry, $user);
    }

    /**
     * Delete an asset, but only if it is in draft status and has no associated financial records.
     * Enforces the accounting principle of immutability for confirmed assets and those with financial history.
     *
     * @param  Asset  $asset  The asset to be deleted.
     * @return bool True on successful deletion.
     *
     * @throws \Jmeryar\Foundation\Exceptions\DeletionNotAllowedException If the asset cannot be deleted due to business rules.
     */
    public function delete(Asset $asset): bool
    {
        // Guard Clause 1: Only allow deleting if the status is Draft.
        if ($asset->status !== AssetStatus::Draft) {
            throw new \Jmeryar\Foundation\Exceptions\DeletionNotAllowedException(
                'Cannot delete a confirmed asset. Only draft assets can be deleted.'
            );
        }

        // Guard Clause 2: Check for any depreciation entries (even draft ones).
        if ($asset->depreciationEntries()->exists()) {
            throw new \Jmeryar\Foundation\Exceptions\DeletionNotAllowedException(
                'Cannot delete an asset with depreciation entries. Depreciation history must be preserved.'
            );
        }

        // Guard Clause 3: Check for any journal entries.
        if ($asset->journalEntries()->exists()) {
            throw new \Jmeryar\Foundation\Exceptions\DeletionNotAllowedException(
                'Cannot delete an asset with associated journal entries. Financial records must be preserved.'
            );
        }

        // If all guards pass, proceed with the deletion.
        $result = $asset->delete();

        return $result !== null ? $result : false;
    }

    /**
     * @return Collection<int, DepreciationEntry>
     */
    public function computeDepreciation(Asset $asset): Collection
    {
        $asset->load('currency');

        // Clear any existing draft entries to avoid duplication
        $asset->depreciationEntries()
            ->where('status', DepreciationEntryStatus::Draft)
            ->delete();

        return match ($asset->depreciation_method) {
            \Jmeryar\Accounting\Enums\Assets\DepreciationMethod::Declining => $this->computeDecliningBalance($asset),
            \Jmeryar\Accounting\Enums\Assets\DepreciationMethod::SumOfDigits => $this->computeSumOfDigits($asset),
            default => $this->computeStraightLine($asset),
        };
    }

    /**
     * Standard Straight Line Depreciation
     * (Cost - Salvage) / Useful Life
     */
    protected function computeStraightLine(Asset $asset): Collection
    {
        $entries = collect();
        $depreciableValue = $asset->purchase_value->minus($asset->salvage_value, RoundingMode::HALF_UP);
        $totalMonths = $asset->useful_life_years * 12;
        $monthlyDepreciation = $depreciableValue->dividedBy($totalMonths, RoundingMode::HALF_UP);

        $purchaseDate = Carbon::parse($asset->purchase_date);

        // Initial Depreciation Date logic
        if ($asset->prorata_temporis) {
            $currentDate = $purchaseDate->copy()->endOfMonth();
        } else {
            $currentDate = $purchaseDate->copy()->endOfMonth();
        }

        // Handle Prorata First Entry
        if ($asset->prorata_temporis) {
            $daysInMonth = $purchaseDate->daysInMonth;
            $daysActive = $daysInMonth - $purchaseDate->day + 1;

            $firstMonthAmount = $monthlyDepreciation->multipliedBy($daysActive)->dividedBy($daysInMonth, RoundingMode::HALF_UP);

            $entries->push($this->createEntry($asset, $firstMonthAmount, $currentDate->copy()));

            // Move to next month end
            $currentDate->startOfMonth()->addMonth()->endOfMonth();
            $totalMonths--;
        }

        $iterations = $asset->prorata_temporis ? $totalMonths : $asset->useful_life_years * 12;

        for ($i = 0; $i < $iterations; $i++) {
            $entries->push($this->createEntry($asset, $monthlyDepreciation, $currentDate->copy()));
            $currentDate->startOfMonth()->addMonth()->endOfMonth();
        }

        // Final Prorata Entry
        if ($asset->prorata_temporis) {
            $currencyCode = $asset->currency->code ?? $asset->company->currency->code;
            $totalDepreciated = \Brick\Money\Money::zero($currencyCode);
            foreach ($entries as $entry) {
                $totalDepreciated = $totalDepreciated->plus($entry->amount);
            }
            $remaining = $depreciableValue->minus($totalDepreciated, RoundingMode::HALF_UP);

            if ($remaining->isPositive()) {
                $entries->push($this->createEntry($asset, $remaining, $currentDate->copy()));
            }
        }

        return $entries;
    }

    /**
     * Declining Balance Method
     * (Book Value - Accumulated Depreciation) * (Rate / Life)
     * Often switches to Straight Line when SL > DB
     */
    protected function computeDecliningBalance(Asset $asset): Collection
    {
        $entries = collect();
        $cost = $asset->purchase_value;
        $salvage = $asset->salvage_value;
        $lifeYears = $asset->useful_life_years;
        $factor = $asset->declining_factor ?? 2.0; // Default to Double Declining

        $currentBookValue = $cost;
        $currencyCode = $asset->currency->code ?? $asset->company->currency->code;
        $totalDepreciation = \Brick\Money\Money::zero($currencyCode);
        $depreciableBase = $cost->minus($salvage, RoundingMode::HALF_UP);

        // Annual Rate = Factor / Life Years
        // We calculate monthly.
        $startDate = Carbon::parse($asset->purchase_date);
        $currentDate = $startDate->copy()->endOfMonth();

        $totalMonths = $lifeYears * 12;

        for ($i = 1; $i <= $totalMonths; $i++) {
            // Calculate Straight Line for remaining life
            $remainingMonths = $totalMonths - ($i - 1);
            // Remaining depreciable amount
            $remainingToDepreciate = $depreciableBase->minus($totalDepreciation, RoundingMode::HALF_UP);

            if ($remainingToDepreciate->isZero() || $remainingToDepreciate->isNegative()) {
                break;
            }

            $slAmount = $remainingToDepreciate->dividedBy($remainingMonths, RoundingMode::HALF_UP);

            // Calculate Declining Balance Amount
            // Rate per month is tricky. Usually done annually then divided.
            // Simplified: (BookValue * Factor) / (LifeYears * 12)
            $dbAmount = $currentBookValue->multipliedBy($factor)->dividedBy($totalMonths, RoundingMode::HALF_UP);

            // Switch to SL if beneficial
            $amount = $slAmount->isGreaterThan($dbAmount) ? $slAmount : $dbAmount;

            // Ensure we don't depreciate past salvage value
            // Allow for small rounding differences
            if ($totalDepreciation->plus($amount)->isGreaterThan($depreciableBase)) {
                $amount = $depreciableBase->minus($totalDepreciation, RoundingMode::HALF_UP);
            }

            $entries->push($this->createEntry($asset, $amount, $currentDate->copy()));

            $totalDepreciation = $totalDepreciation->plus($amount);
            $currentBookValue = $cost->minus($totalDepreciation, RoundingMode::HALF_UP);
            $currentDate->startOfMonth()->addMonth()->endOfMonth();
        }

        return $entries;
    }

    /**
     * Sum of Years' Digits Method
     * (Cost - Salvage) * (Remaining Life / SYD)
     */
    protected function computeSumOfDigits(Asset $asset): Collection
    {
        $entries = collect();
        $depreciableValue = $asset->purchase_value->minus($asset->salvage_value, RoundingMode::HALF_UP);
        $n = $asset->useful_life_years;

        // SYD = n(n+1)/2
        $syd = ($n * ($n + 1)) / 2;

        $currentDate = Carbon::parse($asset->purchase_date)->endOfMonth();

        // SYD is typically annual. We need to distribute annual amount to months.
        for ($year = 1; $year <= $n; $year++) {
            $remainingLife = $n - $year + 1;
            $annualFraction = $remainingLife / $syd;

            // Annual Depreciation Amount
            $annualAmount = $depreciableValue->multipliedBy($annualFraction, RoundingMode::HALF_UP);
            $monthlyAmount = $annualAmount->dividedBy(12, RoundingMode::HALF_UP);

            // Create 12 entries for the year
            for ($month = 1; $month <= 12; $month++) {
                $entries->push($this->createEntry($asset, $monthlyAmount, $currentDate->copy()));
                $currentDate->startOfMonth()->addMonth()->endOfMonth();
            }
        }

        return $entries;
    }

    protected function createEntry(Asset $asset, \Brick\Money\Money $amount, Carbon $date): DepreciationEntry
    {
        return DepreciationEntry::create([
            'asset_id' => $asset->id,
            'company_id' => $asset->company_id,
            'amount' => $amount,
            'depreciation_date' => $date,
            'status' => DepreciationEntryStatus::Draft,
        ]);
    }
}
