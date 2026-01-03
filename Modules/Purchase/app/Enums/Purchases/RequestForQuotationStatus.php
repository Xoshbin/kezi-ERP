<?php

namespace Modules\Purchase\Enums\Purchases;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RequestForQuotationStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Sent = 'sent';
    case BidReceived = 'bid_received';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::BidReceived => 'Bid Received',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'info',
            self::BidReceived => 'warning',
            self::Accepted => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'gray',
        };
    }

    public static function activeStatuses(): array
    {
        return [self::Draft, self::Sent, self::BidReceived];
    }

    public function canBeEdited(): bool
    {
        return in_array($this, [self::Draft, self::Sent, self::BidReceived]);
    }
}
