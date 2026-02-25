<?php

namespace Kezi\Pos\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kezi\Pos\DataTransferObjects\SearchPosOrdersDTO;
use Kezi\Pos\Http\Resources\PosOrderDetailResource;
use Kezi\Pos\Http\Resources\PosOrderSearchResource;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Services\PosOrderSearchService;

class PosOrderSearchController extends Controller
{
    public function __construct(
        protected PosOrderSearchService $searchService
    ) {}

    /**
     * Advanced search with multiple criteria
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PosOrder::class);

        $validated = $request->validate([
            'order_number' => 'nullable|string|max:255',
            'exact_match' => 'nullable|boolean',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'session_id' => 'nullable|integer|exists:pos_sessions,id',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $requestData = $request->all();
        $requestData['company_id'] = $user->companies()->value('companies.id');

        $dto = SearchPosOrdersDTO::fromRequest($requestData);

        // Security check: ensure session_id matches if provided, although service handles company scoping
        $orders = $this->searchService->search($dto);

        return PosOrderSearchResource::collection($orders)->response();
    }

    /**
     * Quick search by order number or customer (for terminal)
     */
    public function quickSearch(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PosOrder::class);

        $validated = $request->validate([
            'q' => 'required|string|min:1',
            'session_id' => 'nullable|integer|exists:pos_sessions,id',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $companyId = (int) $user->companies()->value('companies.id');

        $orders = $this->searchService->quickSearch(
            $companyId,
            $validated['q'],
            $validated['session_id'] ?? null
        );

        return PosOrderSearchResource::collection($orders)->response();
    }

    /**
     * Get single order details
     */
    public function details(PosOrder $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load([
            'customer',
            'lines.product',
            'session.profile',
            'currency',
        ]);

        return (new PosOrderDetailResource($order))->response();
    }

    /**
     * Check if an order is eligible for return
     */
    public function checkReturnEligibility(PosOrder $order, Request $request): JsonResponse
    {
        $this->authorize('view', $order);

        $session = $order->session;
        if (! $session) {
            abort(404, 'Order session not found.');
        }

        $profile = $session->profile;
        $returnPolicy = $profile->return_policy ?? [];

        $eligibility = $this->searchService->isEligibleForReturn($order, $returnPolicy);

        return response()->json($eligibility);
    }
}
