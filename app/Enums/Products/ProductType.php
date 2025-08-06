<?php

namespace App\Enums\Products;

enum ProductType: string
{
    case Product = 'product';
    case Storable = 'storable';
    case CONSUMABLE = 'consumable';
    case SERVICE = 'service';
}
