<?php

namespace Kezi\Pos\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kezi\Pos\DataTransferObjects\SearchPosOrdersDTO;
use Kezi\Pos\Http\Resources\PosOrderDetailResource;
use Kezi\Pos\Http\Resources\PosOrderSearchResource;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Services\PosOrderSearchService;

class PosOrderSearchController
{
    public function __construct(
        protected PosOrderSearchService $searchService
    ) {}

    /**
     * Advanced search with multiple criteria
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => 'nullable|string|max:255',
            'exact_match' => 'nullable|boolean',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'customer_id' => 'nullable|integer|exists:partners,id',
            'customer_name' => 'nullable|string|max:255',
            'amount_min' => 'nullable|integer|min:0',
            'amount_max' => 'nullable|integer|min:0',
            'payment_method' => 'nullable|string',
            'status' => 'nullable|string',
            'product_id' => 'nullable|integer|exists:products,id',
            'product_search' => 'nullable|string|max:255',
            'session_id' => 'nullable|integer|exists:pos_sessions,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $dto = SearchPosOrdersDTO::fromRequest([
            ...$validated,
            'company_id' => $this->resolveCompanyId($request->user()),
        ]);

        $results = $this->searchService->search($dto);

        return response()->json([
            'data' => PosOrderSearchResource::collection($results->items()),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    /**
     * Quick search for POS terminal
     */
    public function quickSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1',
            'session_id' => 'nullable|integer|exists:pos_sessions,id',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $results = $this->searchService->quickSearch(
            companyId: $this->resolveCompanyId($request->user()),
            searchTerm: $validated['q'],
            sessionId: $validated['session_id'] ?? null,
            limit: $validated['limit'] ?? 10
        );

        return response()->json([
            'data' => PosOrderSearchResource::collection($results),
        ]);
    }

    /**
     * Get order details with full information
     */
    public function details(PosOrder $order): JsonResponse
    {
        $order->load([
            'customer',
            'lines.product',
            'session',
            'currency',
            'invoice',
        ]);

        return response()->json([
            'data' => new PosOrderDetailResource($order),
        ]);
    }

    /**
     * Check if order is eligible for return
     */
    public function checkReturnEligibility(PosOrder $order, Request $request): JsonResponse
    {
        $profile = $order->session->profile;
        $returnPolicy = $profile->return_policy ?? [];

        if (! ($returnPolicy['enabled'] ?? false)) {
            return response()->json([
                'eligible' => false,
                'reasons' => ['Returns are not enabled for this POS profile'],
            ]);
        }

        $eligibility = $this->searchService->isEligibleForReturn($order, $returnPolicy);

        return response()->json($eligibility);
    }

    protected function resolveCompanyId(\App\Models\User $user): ?int
    {
        return (int) $user->companies()->value('companies.id') ?: null;
    }
}
