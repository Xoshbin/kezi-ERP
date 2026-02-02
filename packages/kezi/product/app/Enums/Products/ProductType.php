<?php

namespace Kezi\Product\Enums\Products;

enum ProductType: string
{
    case Product = 'product';
    case Storable = 'storable';
    case Consumable = 'consumable';
    case Service = 'service';

    /**
     * Get the translated label for the product type.
     */
    public function label(): string
    {
        return __('enums.product_type.'.$this->value);
    }
}
