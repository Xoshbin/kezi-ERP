<?php

namespace Kezi\Pos\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kezi\Pos\Http\Resources\CategoryResource;
use Kezi\Pos\Http\Resources\CustomerResource;
use Kezi\Pos\Http\Resources\PosProfileResource;
use Kezi\Pos\Http\Resources\ProductResource;
use Kezi\Pos\Http\Resources\TaxResource;
use Kezi\Pos\Services\PosSyncService;

class MasterDataSyncController extends Controller
{
    public function index(Request $request, PosSyncService $service)
    {
        $request->validate([
            'since' => 'nullable|date',
            'company_id' => 'nullable|integer',
        ]);

        $since = $request->input('since') ? \Carbon\Carbon::parse($request->input('since')) : null;
        $companyId = $request->input('company_id');

        $data = $service->getMasterData($request->user(), $since, $companyId);

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
