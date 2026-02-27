<?php

namespace Kezi\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('syncOrders', \Kezi\Pos\Models\PosOrder::class);
    }

    /**
     * @return array<string, array<int, string>>
     */
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
            // Split payments (optional — backward compatible with legacy single payment_method)
            'orders.*.payments' => ['nullable', 'array'],
            'orders.*.payments.*.method' => ['required_with:orders.*.payments', 'string'],
            'orders.*.payments.*.amount' => ['required_with:orders.*.payments', 'integer', 'min:1'],
            'orders.*.payments.*.amount_tendered' => ['nullable', 'integer'],
            'orders.*.payments.*.change_given' => ['nullable', 'integer'],
        ];
    }
}
