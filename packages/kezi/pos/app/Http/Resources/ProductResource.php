<?php

namespace Kezi\Pos\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', app()->getLocale()),
            'sku' => $this->sku,
            'description' => $this->getTranslation('description', app()->getLocale()) ?? '',
            'unit_price' => $this->unit_price?->getMinorAmount()->toInt() ?? 0,
            'currency_code' => $this->unit_price?->getCurrency()->getCurrencyCode(),
            'category_id' => $this->product_category_id,
            'type' => $this->type?->value,
            'available_quantity' => $this->available_quantity,
            'tax_ids' => $this->purchaseTaxes->pluck('id')->values()->all(),
            'is_active' => $this->is_active,
        ];
    }
}
