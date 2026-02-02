<?php

namespace Kezi\Inventory\Enums\Inventory;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LandedCostStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Posted => 'Posted',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Posted => 'success',
            self::Cancelled => 'danger',
        };
    }
}
