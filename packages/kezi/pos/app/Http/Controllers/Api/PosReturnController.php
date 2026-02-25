<?php

namespace Kezi\Pos\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Kezi\Pos\Actions\ApprovePosReturnAction;
use Kezi\Pos\Actions\CreatePosReturnAction;
use Kezi\Pos\Actions\ProcessPosReturnAction;
use Kezi\Pos\Actions\RejectPosReturnAction;
use Kezi\Pos\Actions\SubmitPosReturnAction;
use Kezi\Pos\DataTransferObjects\CreatePosReturnDTO;
use Kezi\Pos\DataTransferObjects\CreatePosReturnLineDTO;
use Kezi\Pos\Models\PosReturn;

class PosReturnController extends Controller
{
    public function store(Request $request, CreatePosReturnAction $action): JsonResponse
    {
        $this->authorize('create', PosReturn::class);

        $validated = $request->validate([
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
        ]);

        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $companyId = $user?->getAttribute('current_company_id');

        if (! $companyId) {
            $tenant = \Filament\Facades\Filament::getTenant();
            $companyId = $tenant?->getKey();
        }

        $dto = new CreatePosReturnDTO(
            company_id: (int) $companyId,
            pos_session_id: (int) $validated['pos_session_id'],
            original_order_id: (int) $validated['original_order_id'],
            currency_id: (int) $validated['currency_id'],
            return_date: Carbon::parse($validated['return_date']),
            return_reason: $validated['return_reason'],
            return_notes: $validated['return_notes'],
            requested_by_user_id: (int) $request->user()?->id,
            refund_method: $validated['refund_method'],
            lines: array_map(fn ($line) => new CreatePosReturnLineDTO(
                original_order_line_id: (int) $line['original_order_line_id'],
                product_id: (int) $line['product_id'],
                quantity_returned: (float) $line['quantity_returned'],
                quantity_available: (float) $line['quantity_available'],
                unit_price: (int) $line['unit_price'],
                refund_amount: (int) $line['refund_amount'],
                restocking_fee_line: (int) $line['restocking_fee_line'],
                restock: (bool) $line['restock'],
                item_condition: $line['item_condition'] ?? null,
                return_reason_line: $line['return_reason_line'] ?? null,
                metadata: $line['metadata'] ?? null,
            ), $validated['lines']),
        );

        $return = $action->execute($dto);

        return response()->json($return);
    }

    public function submit(PosReturn $return, SubmitPosReturnAction $action): JsonResponse
    {
        $this->authorize('submit', $return);

        $return = $action->execute($return);

        return response()->json($return);
    }

    public function approve(PosReturn $return, ApprovePosReturnAction $action): JsonResponse
    {
        $this->authorize('approve', $return);

        /** @var \App\Models\User $user */
        $return = $action->execute($return, $user);

        return response()->json($return);
    }

    public function reject(Request $request, PosReturn $return, RejectPosReturnAction $action): JsonResponse
    {
        $this->authorize('reject', $return);

        $validated = $request->validate([
            'reason' => ['required', 'string'],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $return = $action->execute($return, $user, $validated['reason']);

        return response()->json($return);
    }

    public function process(PosReturn $return, ProcessPosReturnAction $action): JsonResponse
    {
        $this->authorize('process', $return);

        /** @var \App\Models\User $user */
        $return = $action->execute($return, $user);

        return response()->json($return);
    }
}
