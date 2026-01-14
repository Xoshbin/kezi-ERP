<?php

namespace Modules\Payment\Enums\PettyCash;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PettyCashVoucherStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Posted = 'posted';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => __('accounting::petty_cash.status.draft'),
            self::Posted => __('accounting::petty_cash.status.posted'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Posted => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft => 'heroicon-m-pencil-square',
            self::Posted => 'heroicon-m-check-badge',
        };
    }
}
