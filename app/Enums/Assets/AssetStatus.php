<?php

namespace App\Enums\Assets;

enum AssetStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Depreciating = 'depreciating';
    case FullyDepreciated = 'fully_depreciated';
    case Sold = 'sold';

    /**
     * Get the translated label for the asset status.
     */
    public function label(): string
    {
        return __('enums.asset_status.'.$this->value);
    }
}
