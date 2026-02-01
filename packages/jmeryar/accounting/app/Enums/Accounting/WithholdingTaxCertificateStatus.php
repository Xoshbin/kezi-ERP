<?php

namespace Jmeryar\Accounting\Enums\Accounting;

enum WithholdingTaxCertificateStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Cancelled = 'cancelled';

    /**
     * Get the translated label for display.
     */
    public function label(): string
    {
        return __('enums.withholding_tax_certificate_status.'.$this->value);
    }

    /**
     * Get the color for Filament badges.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Issued => 'success',
            self::Cancelled => 'danger',
        };
    }
}
