<?php

namespace Modules\Foundation\Enums\Partners;

enum PartnerType: string
{
    case Customer = 'customer';
    case Vendor = 'vendor';
    case Both = 'both';

    /**
     * Get the translated label for the partner type.
     */
    public function label(): string
    {
        return __('foundation::enums.partner_type.'.$this->value);
    }
}
