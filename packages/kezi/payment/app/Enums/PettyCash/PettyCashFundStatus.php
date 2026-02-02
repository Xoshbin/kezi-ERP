<?php

namespace Kezi\Payment\Enums\PettyCash;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PettyCashFundStatus: string implements HasColor, HasIcon, HasLabel
{
    case Active = 'active';
    case Closed = 'closed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Active => __('accounting::petty_cash.status.active'),
            self::Closed => __('accounting::petty_cash.status.closed'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => 'success',
            self::Closed => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Active => 'heroicon-m-check-circle',
            self::Closed => 'heroicon-m-lock-closed',
        };
    }
}
