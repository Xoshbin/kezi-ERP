<?php

namespace Kezi\Accounting\Observers;

use Kezi\Accounting\Enums\Assets\DepreciationEntryStatus;
use Kezi\Accounting\Models\DepreciationEntry;

class DepreciationEntryObserver
{
    public function updating(DepreciationEntry $depreciationEntry): void
    {
        if ($depreciationEntry->getOriginal('status') === DepreciationEntryStatus::Posted) {
            throw new \Kezi\Foundation\Exceptions\UpdateNotAllowedException(__('accounting::exceptions.asset.posted_depreciation_cannot_be_updated'));
        }
    }

    public function deleting(DepreciationEntry $depreciationEntry): void
    {
        if ($depreciationEntry->status === DepreciationEntryStatus::Posted) {
            throw new \Kezi\Foundation\Exceptions\DeletionNotAllowedException(__('accounting::exceptions.asset.posted_depreciation_cannot_be_deleted'));
        }
    }
}
