<?php

namespace App\Enums\Partners;

enum PartnerType: string
{
    case Customer = 'customer';
    case Vendor = 'vendor';

    /**
     * Get the translated label for the partner type.
     */
    public function label(): string
    {
        return __('enums.partner_type.' . $this->value);
    }
}
