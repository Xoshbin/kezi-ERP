<?php

namespace Kezi\Pos\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kezi\Pos\Http\Requests\CloseSessionRequest;
use Kezi\Pos\Http\Requests\OpenSessionRequest;
use Kezi\Pos\Http\Resources\PosSessionResource;
use Kezi\Pos\Models\PosProfile;
use Kezi\Pos\Models\PosSession;

class SessionController extends Controller
{
    public function open(OpenSessionRequest $request): JsonResponse
    {
        $this->authorize('open', PosSession::class);

        // One session per user at a time across any profile
        /** @var \App\Models\User $user */
        $user = $request->user();

        $existing = PosSession::with('profile')
            ->where('user_id', $user->id)
            ->where('status', 'opened')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'User already has an open session on profile: '.$existing->profile->name,
                'session' => new PosSessionResource($existing),
            ], 409);
        }

        /** @var PosProfile $profile */
        $profile = PosProfile::with('company.currency')->findOrFail($request->pos_profile_id);
        $currency = $profile->company->currency;

        $session = PosSession::create([
            'pos_profile_id' => $profile->id,
            'user_id' => $user->id,
            'company_id' => $profile->company_id,
            'opened_at' => now(),
            'opening_cash' => \Brick\Money\Money::ofMinor($request->opening_cash, $currency->code),
            'status' => 'opened',
        ]);

        return response()->json([
            'message' => 'Session opened',
            'session' => new PosSessionResource($session->load(['profile', 'user'])),
        ], 201);
    }

    public function close(CloseSessionRequest $request, PosSession $session): JsonResponse
    {
        $this->authorize('close', $session);

        /** @var \App\Models\User $user */
        if ($session->status !== 'opened') {
            return response()->json(['message' => 'Session is already closed or not open'], 409);
        }

        $session->load('profile.company.currency');
        $currency = $session->profile->company->currency;

        $session->update([
            'closed_at' => now(),
            'closing_cash' => \Brick\Money\Money::ofMinor($request->closing_cash, $currency->code),
            'closing_notes' => $request->closing_notes,
            'status' => 'closed',
        ]);

        $summary = [
            'order_count' => $session->orders()->count(),
            'total_revenue' => (int) $session->orders()->sum('total_amount'),
        ];

        return response()->json([
            'message' => 'Session closed',
            'session' => new PosSessionResource($session),
            'summary' => $summary,
        ]);
    }

    public function current(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PosSession::class);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $session = PosSession::where('user_id', $user->id)
            ->where('status', 'opened')
            ->latest()
            ->with(['profile', 'user', 'orders'])
            ->first();

        if (! $session) {
            return response()->json(['message' => 'No open session'], 404);
        }

        return response()->json([
            'session' => new PosSessionResource($session),
            'order_count' => $session->orders->count(),
        ]);
    }
}
