<?php

namespace Jmeryar\Accounting\Enums\Assets;

enum DepreciationEntryStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';

    /**
     * Get the translated label for the depreciation entry status.
     */
    public function label(): string
    {
        return __('enums.depreciation_entry_status.'.$this->value);
    }
}
