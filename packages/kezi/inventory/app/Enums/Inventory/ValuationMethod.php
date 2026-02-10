<?php

namespace Kezi\Inventory\Enums\Inventory;

enum ValuationMethod: string
{
    case Fifo = 'fifo';
    case Lifo = 'lifo';
    case Avco = 'avco';
    case Standard = 'standard_price';

    /**
     * Get the translated label for the valuation method.
     */
    public function label(): string
    {
        return __('enums.valuation_method.'.$this->value);
    }
}
