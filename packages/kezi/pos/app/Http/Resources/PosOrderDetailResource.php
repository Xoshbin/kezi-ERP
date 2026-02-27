<?php

namespace Kezi\Pos\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PosOrderDetailResource extends JsonResource
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
            'total_tax' => $this->total_tax->getMinorAmount()->toInt(),
            'discount_amount' => $this->discount_amount->getMinorAmount()->toInt(),
            'currency_code' => $this->currency->code,
            'notes' => $this->notes,

            'customer' => $this->when($this->customer, [
                'id' => $this->customer?->id,
                'name' => $this->customer?->name,
                'email' => $this->customer?->email,
                'phone' => $this->customer?->phone,
            ]),

            'session' => [
                'id' => $this->session->id,
                'opened_at' => $this->session->opened_at->toIso8601String(),
            ],

            'lines' => $this->lines->map(fn ($line) => [
                'id' => $line->id,
                'product_id' => $line->product_id,
                'product_name' => $line->product->name,
                'product_sku' => $line->product->sku,
                'quantity' => (float) $line->quantity,
                'unit_price' => $line->unit_price->getMinorAmount()->toInt(),
                'discount_amount' => $line->discount_amount->getMinorAmount()->toInt(),
                'tax_amount' => $line->tax_amount->getMinorAmount()->toInt(),
                'total_amount' => $line->total_amount->getMinorAmount()->toInt(),
                'metadata' => $line->metadata,
            ]),

            'invoice_id' => $this->invoice_id,
        ];
    }
}
