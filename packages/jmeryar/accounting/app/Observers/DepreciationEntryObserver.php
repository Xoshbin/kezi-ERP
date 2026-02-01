<?php

namespace Jmeryar\Accounting\Observers;

use Jmeryar\Accounting\Enums\Assets\DepreciationEntryStatus;
use Jmeryar\Accounting\Models\DepreciationEntry;

class DepreciationEntryObserver
{
    public function updating(DepreciationEntry $depreciationEntry): void
    {
        if ($depreciationEntry->getOriginal('status') === DepreciationEntryStatus::Posted) {
            throw new \Jmeryar\Foundation\Exceptions\UpdateNotAllowedException('Posted depreciation entries cannot be updated.');
        }
    }

    public function deleting(DepreciationEntry $depreciationEntry): void
    {
        if ($depreciationEntry->status === DepreciationEntryStatus::Posted) {
            throw new \Jmeryar\Foundation\Exceptions\DeletionNotAllowedException('Posted depreciation entries cannot be deleted.');
        }
    }
}
