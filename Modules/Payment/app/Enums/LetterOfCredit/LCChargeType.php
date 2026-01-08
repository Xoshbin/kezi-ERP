<?php

namespace Modules\Payment\Enums\LetterOfCredit;

use Filament\Support\Contracts\HasLabel;

enum LCChargeType: string implements HasLabel
{
    case IssuanceFee = 'issuance_fee';
    case AmendmentFee = 'amendment_fee';
    case NegotiationFee = 'negotiation_fee';
    case SwiftCharges = 'swift_charges';
    case ConfirmationFee = 'confirmation_fee';
    case DiscrepancyFee = 'discrepancy_fee';
    case Other = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::IssuanceFee => 'Issuance Fee',
            self::AmendmentFee => 'Amendment Fee',
            self::NegotiationFee => 'Negotiation Fee',
            self::SwiftCharges => 'SWIFT Charges',
            self::ConfirmationFee => 'Confirmation Fee',
            self::DiscrepancyFee => 'Discrepancy Fee',
            self::Other => 'Other Charges',
        };
    }
}
