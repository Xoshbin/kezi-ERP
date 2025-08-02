<?php

namespace App\Enums\Assets;

enum AssetStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Depreciating = 'depreciating';
    case FullyDepreciated = 'fully_depreciated';
    case Sold = 'sold';
}
