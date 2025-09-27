<?php

namespace App\Observers;

use App\Enums\Assets\DepreciationEntryStatus;
use App\Exceptions\DeletionNotAllowedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\DepreciationEntry;

class DepreciationEntryObserver
{
    public function updating(DepreciationEntry $depreciationEntry): void
    {
        if ($depreciationEntry->getOriginal('status') === DepreciationEntryStatus::Posted) {
            throw new UpdateNotAllowedException('Posted depreciation entries cannot be updated.');
        }
    }

    public function deleting(DepreciationEntry $depreciationEntry): void
    {
        if ($depreciationEntry->status === DepreciationEntryStatus::Posted) {
            throw new DeletionNotAllowedException('Posted depreciation entries cannot be deleted.');
        }
    }
}
