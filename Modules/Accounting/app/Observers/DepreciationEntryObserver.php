<?php

namespace Modules\Accounting\Observers;

use App\Enums\Assets\DepreciationEntryStatus;
use App\Exceptions\DeletionNotAllowedException;
use App\Exceptions\UpdateNotAllowedException;

class DepreciationEntryObserver
{
    public function updating(\Modules\Accounting\Models\DepreciationEntry $depreciationEntry): void
    {
        if ($depreciationEntry->getOriginal('status') === DepreciationEntryStatus::Posted) {
            throw new \Modules\Foundation\Exceptions\UpdateNotAllowedException('Posted depreciation entries cannot be updated.');
        }
    }

    public function deleting(\Modules\Accounting\Models\DepreciationEntry $depreciationEntry): void
    {
        if ($depreciationEntry->status === DepreciationEntryStatus::Posted) {
            throw new \Modules\Foundation\Exceptions\DeletionNotAllowedException('Posted depreciation entries cannot be deleted.');
        }
    }
}
