<?php

namespace App\Actions\Assets;

use App\Enums\Assets\DepreciationEntryStatus;
use App\Enums\Assets\DepreciationMethod;
use App\Models\Asset;
use App\Models\DepreciationEntry;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

class ComputeDepreciationScheduleAction
{
    public function execute(Asset $asset): void
    {
        DB::transaction(function () use ($asset) {
            // Clear existing draft entries
            $asset->depreciationEntries()->where('status', DepreciationEntryStatus::Draft)->delete();

            if ($asset->depreciation_method === DepreciationMethod::StraightLine) {
                $this->computeStraightLine($asset);
            }
            // Other methods can be added here
        });
    }

    private function computeStraightLine(Asset $asset): void
    {
        $depreciableValue = $asset->purchase_value->minus($asset->salvage_value);
        $totalMonths = $asset->useful_life_years * 12;

        if ($totalMonths <= 0) {
            return;
        }

        $monthlyDepreciation = $depreciableValue->dividedBy($totalMonths, RoundingMode::HALF_UP);

        $depreciationDate = $asset->purchase_date->copy()->startOfMonth()->addMonth();

        for ($i = 0; $i < $totalMonths; $i++) {
            DepreciationEntry::create([
                'asset_id' => $asset->id,
                'company_id' => $asset->company_id,
                'depreciation_date' => $depreciationDate->copy(),
                'amount' => $monthlyDepreciation,
                'status' => DepreciationEntryStatus::Draft,
            ]);

            $depreciationDate->addMonth();
        }
    }
}
