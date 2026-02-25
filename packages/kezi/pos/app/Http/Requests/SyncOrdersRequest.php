<?php

namespace Kezi\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('syncOrders', \Kezi\Pos\Models\PosOrder::class);
    }

    public function rules(): array
    {
        return [
            'orders' => ['required', 'array'],
            'orders.*.uuid' => ['required', 'uuid'],
            'orders.*.currency_id' => ['required', 'integer'],
            'orders.*.pos_session_id' => ['required', 'integer'],
            'orders.*.total_amount' => ['required'],
            'orders.*.discount_amount' => ['nullable', 'integer'],
            'orders.*.lines' => ['array'],
            'orders.*.lines.*.discount_amount' => ['nullable', 'integer'],
        ];
    }
}
