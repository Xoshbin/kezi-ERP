<?php

namespace Kezi\Pos\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kezi\Pos\Actions\SyncOrdersAction;
use Kezi\Pos\DataTransferObjects\PosOrderData;

class OrderSyncController extends Controller
{
    public function store(Request $request, SyncOrdersAction $action)
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

        $ordersData = PosOrderData::collect($request->input('orders', []));
        $user = $request->user();
        $companyId = $request->input('company_id') ?? $user->companies()->first()?->id;

        if (! $companyId) {
            return response()->json(['message' => 'Company ID required'], 400);
        }

        $result = $action->execute($ordersData, $user, $companyId);

        return response()->json($result);
    }
}
