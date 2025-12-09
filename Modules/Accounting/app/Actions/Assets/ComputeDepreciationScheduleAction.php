<?php

namespace Modules\Accounting\Actions\Assets;

use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Enums\Assets\DepreciationEntryStatus;
use Modules\Accounting\Enums\Assets\DepreciationMethod;
use Modules\Accounting\Models\Asset;
use Modules\Accounting\Models\DepreciationEntry;

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
