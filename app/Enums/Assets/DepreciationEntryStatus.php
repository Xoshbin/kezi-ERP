<?php

namespace App\Enums\Assets;

enum DepreciationEntryStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
}
