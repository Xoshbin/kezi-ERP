<?php

namespace Kezi\Inventory\Enums\Inventory;

enum ReorderingRoute: string
{
    case MinMax = 'min_max';
    case MTO = 'mto'; // Make-to-Order

    /**
     * Get the translated label for the reordering route.
     */
    public function label(): string
    {
        return __('enums.reordering_route.'.$this->value);
    }
}
