<?php

namespace Kezi\Pos\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kezi\Pos\Actions\SyncOrdersAction;
use Kezi\Pos\DataTransferObjects\PosOrderData;

class OrderSyncController extends Controller
{
    public function store(Request $request, SyncOrdersAction $action): JsonResponse
    {
        $request->validate([
            'orders' => 'required|array',
            'orders.*.uuid' => 'required|uuid',
            'orders.*.currency_id' => 'required|integer',
            'orders.*.pos_session_id' => 'required|integer',
            'orders.*.total_amount' => 'required',
            'orders.*.discount_amount' => 'nullable|integer',
            'orders.*.lines' => 'array',
            'orders.*.lines.*.discount_amount' => 'nullable|integer',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        // Company ID is always derived from the authenticated user — never trusted from input.
        $companyId = (int) $user->companies()->value('companies.id') ?: null;

        if (! $companyId) {
            return response()->json(['message' => 'No company associated with this user'], 400);
        }

        $ordersData = PosOrderData::collect($request->input('orders', []));
        $result = $action->execute($ordersData, $user, $companyId);

        return response()->json($result);
    }
}
