<?php

namespace Kezi\Pos\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kezi\Product\Models\Product;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Product $product */
        $product = $this->resource;

        return [
            'id' => $product->id,
            'category_id' => $product->category_id,
            'name' => $product->getTranslation('name', app()->getLocale()),
            'sku' => $product->sku,
            'description' => $product->getTranslation('description', app()->getLocale()) ?? '',
            'unit_price' => $product->unit_price?->getMinorAmount()->toInt() ?? 0,
            'currency_code' => $product->unit_price?->getCurrency()->getCurrencyCode(),
            'type' => $product->type->value,
            'available_quantity' => $product->available_quantity,
            'tax_ids' => $product->purchaseTaxes->pluck('id')->values()->all(),
            'is_active' => $product->is_active ? 1 : 0,
        ];
    }
}
