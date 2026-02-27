<?php

namespace Kezi\Pos\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kezi\Pos\Http\Resources\CategoryResource;
use Kezi\Pos\Http\Resources\CustomerResource;
use Kezi\Pos\Http\Resources\PosProfileResource;
use Kezi\Pos\Http\Resources\ProductResource;
use Kezi\Pos\Http\Resources\TaxResource;
use Kezi\Pos\Services\PosSyncService;

class MasterDataSyncController extends Controller
{
    public function index(Request $request, PosSyncService $service): JsonResponse
    {
        $this->authorize('viewAny', \Kezi\Pos\Models\PosOrder::class);

        $request->validate([
            'since' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:2000',
        ]);

        $since = $request->input('since') ? \Carbon\Carbon::parse($request->input('since')) : null;
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 500);

        /** @var \App\Models\User $user */
        $user = $request->user();

        // company_id is NEVER accepted from user input — it is always scoped to the authenticated user.
        $data = $service->getMasterData($user, $since, null, $page, $limit);

        return response()->json([
            'products' => ProductResource::collection($data['products']),
            'categories' => CategoryResource::collection($data['categories']),
            'taxes' => TaxResource::collection($data['taxes']),
            'customers' => CustomerResource::collection($data['customers']),
            'profiles' => PosProfileResource::collection($data['profiles']),
            'currencies' => $data['currencies'] ?? [],
            'company_currency' => $data['company_currency'] ?? null,
            'has_more' => $data['hasMore'] ?? false,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
