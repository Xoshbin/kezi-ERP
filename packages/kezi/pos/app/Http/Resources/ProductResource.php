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
            'name' => $this->name,
            'sku' => $this->sku,
            'unit_price' => $this->unit_price?->getMinorAmount()->toInt() ?? 0,
            'category_id' => $this->product_category_id, // Check actual column name
            'tax_id' => $this->tax_id ?? null,
            // 'stock' => $this->inventory_count ?? 0, // Simplified for now
        ];
    }
}
