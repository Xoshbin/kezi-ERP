<?php

namespace App\Enums\Inventory;

enum ValuationMethod: string
{
    //TODO::Change the cases to PascalCase
    case FIFO = 'fifo';
    case LIFO = 'lifo';
    case AVCO = 'avco';
    case STANDARD = 'standard_price';

    /**
     * Get the translated label for the valuation method.
     */
    public function label(): string
    {
        return __('enums.valuation_method.' . $this->value);
    }
}
