<?php

namespace Kezi\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Kezi\Pos\Models\PosReturn;

class StorePosReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PosReturn::class);
    }

    public function rules(): array
    {
        return [
            'pos_session_id' => ['required', 'exists:pos_sessions,id'],
            'original_order_id' => ['required', 'exists:pos_orders,id'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'return_date' => ['required', 'date'],
            'return_reason' => ['required', 'string'],
            'return_notes' => ['nullable', 'string'],
            'refund_method' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.original_order_line_id' => ['required', 'exists:pos_order_lines,id'],
            'lines.*.product_id' => ['required', 'exists:products,id'],
            'lines.*.quantity_returned' => ['required', 'numeric', 'min:0'],
            'lines.*.quantity_available' => ['required', 'numeric'],
            'lines.*.unit_price' => ['required', 'integer'],
            'lines.*.refund_amount' => ['required', 'integer'],
            'lines.*.restocking_fee_line' => ['required', 'integer'],
            'lines.*.restock' => ['required', 'boolean'],
            'lines.*.item_condition' => ['nullable', 'string'],
            'lines.*.return_reason_line' => ['nullable', 'string'],
            'lines.*.metadata' => ['nullable', 'array'],
        ];
    }
}
