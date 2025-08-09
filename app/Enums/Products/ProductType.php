<?php

namespace App\Enums\Products;

enum ProductType: string
{
    case Product = 'product';
    case Storable = 'storable';
    case CONSUMABLE = 'consumable';
    case SERVICE = 'service';

    /**
     * Get the translated label for the product type.
     */
    public function label(): string
    {
        return __('enums.product_type.' . $this->value);
    }
}
