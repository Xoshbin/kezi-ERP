<?php

namespace Kezi\Pos\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Kezi\Pos\Actions\SyncOrdersAction;
use Kezi\Pos\DataTransferObjects\PosOrderData;
use Kezi\Pos\Http\Requests\SyncOrdersRequest;

class OrderSyncController extends Controller
{
    public function store(SyncOrdersRequest $request, SyncOrdersAction $action): JsonResponse
    {

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
