<?php

namespace Modules\Payment\Enums\Cheques;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ChequeStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';           // Just created, not printed
    case Printed = 'printed';       // Printed but not handed over
    case HandedOver = 'handed_over'; // Given to payee/deposited by us (Payable)
    case Deposited = 'deposited';   // Sent to bank for collection (Receivable)
    case Cleared = 'cleared';       // Successfully cashed
    case Bounced = 'bounced';       // Returned unpaid
    case Cancelled = 'cancelled';   // Cancelled before handover
    case Voided = 'voided';         // Destroyed/voided cheque

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Printed => 'Printed',
            self::HandedOver => 'Handed Over',
            self::Deposited => 'Deposited',
            self::Cleared => 'Cleared',
            self::Bounced => 'Bounced',
            self::Cancelled => 'Cancelled',
            self::Voided => 'Voided',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Printed => 'info',
            self::HandedOver, self::Deposited => 'warning',
            self::Cleared => 'success',
            self::Bounced => 'danger',
            self::Cancelled, self::Voided => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft => 'heroicon-m-pencil-square',
            self::Printed => 'heroicon-m-printer',
            self::HandedOver => 'heroicon-m-arrow-right-start-on-rectangle',
            self::Deposited => 'heroicon-m-building-library',
            self::Cleared => 'heroicon-m-check-circle',
            self::Bounced => 'heroicon-m-x-circle',
            self::Cancelled => 'heroicon-m-no-symbol',
            self::Voided => 'heroicon-m-trash',
        };
    }
}
