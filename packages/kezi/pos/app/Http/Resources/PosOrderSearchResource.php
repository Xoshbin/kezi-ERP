<?php

namespace Kezi\Pos\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PosOrderSearchResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'order_number' => $this->order_number,
            'ordered_at' => $this->ordered_at->toIso8601String(),
            'status' => $this->status,
            'payment_method' => $this->payment_method?->value,
            'total_amount' => $this->total_amount->getMinorAmount()->toInt(),
            'total_amount_formatted' => $this->total_amount->formatTo('en_US'),
            'currency_code' => $this->currency->code,
            'customer' => $this->when($this->customer, [
                'id' => $this->customer?->id,
                'name' => $this->customer?->name,
            ]),
            'items_count' => $this->lines->count(),
        ];
    }
}
