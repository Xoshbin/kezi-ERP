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
        $request->validate([
            'since' => 'nullable|date',
        ]);

        $since = $request->input('since') ? \Carbon\Carbon::parse($request->input('since')) : null;

        // company_id is NEVER accepted from user input — it is always scoped to the authenticated user.
        $data = $service->getMasterData($request->user(), $since, null);

        return response()->json([
            'products' => ProductResource::collection($data['products']),
            'categories' => CategoryResource::collection($data['categories']),
            'taxes' => TaxResource::collection($data['taxes']),
            'customers' => CustomerResource::collection($data['customers']),
            'profiles' => PosProfileResource::collection($data['profiles']),
            'currencies' => $data['currencies'] ?? [],
            'company_currency' => $data['company_currency'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
