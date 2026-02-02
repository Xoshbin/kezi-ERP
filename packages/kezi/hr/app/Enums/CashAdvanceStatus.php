<?php

namespace Kezi\HR\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum CashAdvanceStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Disbursed = 'disbursed';
    case PendingSettlement = 'pending_settlement';
    case Settled = 'settled';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingApproval => 'Pending Approval',
            self::Approved => 'Approved',
            self::Disbursed => 'Disbursed',
            self::PendingSettlement => 'Pending Settlement',
            self::Settled => 'Settled',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingApproval => 'warning',
            self::Approved => 'info',
            self::Disbursed => 'primary',
            self::PendingSettlement => 'warning',
            self::Settled => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft => 'heroicon-m-pencil-square',
            self::PendingApproval => 'heroicon-m-clock',
            self::Approved => 'heroicon-m-check-circle',
            self::Disbursed => 'heroicon-m-banknotes',
            self::PendingSettlement => 'heroicon-m-document-text',
            self::Settled => 'heroicon-m-check-badge',
            self::Rejected => 'heroicon-m-x-circle',
            self::Cancelled => 'heroicon-m-no-symbol',
        };
    }
}
