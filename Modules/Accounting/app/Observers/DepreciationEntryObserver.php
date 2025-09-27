<?php

namespace Modules\Accounting\Observers;

use Modules\Accounting\Models\DepreciationEntry;

class DepreciationEntryObserver
{
    public function updating(DepreciationEntry $depreciationEntry): void
    {
        if ($depreciationEntry->getOriginal('status') === DepreciationEntryStatus::Posted) {
            throw new \Modules\Foundation\Exceptions\UpdateNotAllowedException('Posted depreciation entries cannot be updated.');
        }
    }

    public function deleting(DepreciationEntry $depreciationEntry): void
    {
        if ($depreciationEntry->status === DepreciationEntryStatus::Posted) {
            throw new \Modules\Foundation\Exceptions\DeletionNotAllowedException('Posted depreciation entries cannot be deleted.');
        }
    }
}
