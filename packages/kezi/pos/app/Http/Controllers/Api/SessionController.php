<?php

namespace Kezi\Pos\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kezi\Foundation\Casts\MoneyCast;
use Kezi\Pos\Models\PosProfile;
use Kezi\Pos\Models\PosSession;

class SessionController extends Controller
{
    public function open(Request $request)
    {
        $request->validate([
            'pos_profile_id' => 'required|exists:pos_profiles,id',
            'opening_cash' => 'required|numeric|min:0', // Amount in major units or minor?
            // MoneyCast usually expects specific format.
            // If strictly API, usually minor units (integers).
            // But validation rule 'numeric' allows floats.
            // Let's assume minor units (int) for API to be consistent with MoneyCast logic which might map to integer column.
        ]);

        $profile = PosProfile::with('company.currency')->findOrFail($request->pos_profile_id);
        $currency = $profile->company->currency;

        // ... existing check ...
        $existing = PosSession::where('user_id', $request->user()->id)
            ->where('pos_profile_id', $profile->id)
            ->where('status', 'opened') // or 'opening'
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Session already open',
                'session' => $existing,
            ], 409);
        }

        $session = PosSession::create([
            'pos_profile_id' => $profile->id,
            'user_id' => $request->user()->id,
            'opened_at' => now(),
            'opening_cash' => \Brick\Money\Money::ofMinor($request->opening_cash, $currency->code),
            'status' => 'opened',
        ]);

        return response()->json([
            'message' => 'Session opened',
            'session' => $session,
        ], 201);
    }

    public function close(Request $request, PosSession $session)
    {
        // Validation: Ensure session belongs to user
        if ($session->user_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate([
            'closing_cash' => 'required|numeric|min:0',
        ]);

        $session->load('profile.company.currency');
        $currency = $session->profile->company->currency;

        $session->update([
            'closed_at' => now(),
            'closing_cash' => \Brick\Money\Money::ofMinor($request->closing_cash, $currency->code),
            'status' => 'closed',
        ]);

        return response()->json([
            'message' => 'Session closed',
            'session' => $session,
        ]);
    }

    public function current(Request $request)
    {
        $session = PosSession::where('user_id', $request->user()->id)
            ->where('status', 'opened')
            ->latest()
            ->with('profile')
            ->first();

        if (! $session) {
            return response()->json(['message' => 'No open session'], 404);
        }

        return response()->json(['session' => $session]);
    }
}
