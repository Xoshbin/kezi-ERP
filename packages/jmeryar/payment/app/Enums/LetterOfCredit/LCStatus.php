<?php

namespace Jmeryar\Payment\Enums\LetterOfCredit;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum LCStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';                       // LC request created
    case Issued = 'issued';                     // Bank has issued the LC
    case PartiallyUtilized = 'partially_utilized'; // Some bills linked
    case FullyUtilized = 'fully_utilized';      // Fully drawn
    case Expired = 'expired';                   // Past expiry date without full utilization
    case Cancelled = 'cancelled';               // Cancelled before utilization

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Issued',
            self::PartiallyUtilized => 'Partially Utilized',
            self::FullyUtilized => 'Fully Utilized',
            self::Expired => 'Expired',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Issued => 'info',
            self::PartiallyUtilized => 'warning',
            self::FullyUtilized => 'success',
            self::Expired => 'danger',
            self::Cancelled => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft => 'heroicon-m-pencil-square',
            self::Issued => 'heroicon-m-document-check',
            self::PartiallyUtilized => 'heroicon-m-banknotes',
            self::FullyUtilized => 'heroicon-m-check-circle',
            self::Expired => 'heroicon-m-clock',
            self::Cancelled => 'heroicon-m-x-circle',
        };
    }
}
