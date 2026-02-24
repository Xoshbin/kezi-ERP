<?php

namespace Kezi\Pos\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Kezi\Pos\Actions\ApprovePosReturnAction;
use Kezi\Pos\Models\PosReturn;

class ManagerPinController extends Controller
{
    /**
     * Verify a manager's PIN and, if correct, immediately approve a POS return.
     *
     * The POS terminal sends the return ID plus the manager's PIN (4-6 digits).
     * We look up any user in the company that has a matching PIN hash and has
     * the `approve_pos_returns` permission (or, for simplicity, any active
     * company user). On success we approve the return on the manager's behalf.
     */
    public function verifyAndApprove(
        Request $request,
        PosReturn $return,
        ApprovePosReturnAction $approveAction
    ): JsonResponse {
        $validated = $request->validate([
            'pin' => ['required', 'string', 'digits_between:4,8'],
        ]);

        /** @var \App\Models\User $cashier */
        $cashier = $request->user();

        // Resolve company id from the authenticated cashier
        $companyId = (int) $cashier->companies()->value('companies.id');

        // Find a manager in the same company whose PIN matches
        $manager = \App\Models\User::query()
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->where('id', '!=', $cashier->id) // Manager must be someone else
            ->get()
            ->first(fn ($u) => ! empty($u->pos_manager_pin) && Hash::check($validated['pin'], $u->pos_manager_pin));

        if (! $manager) {
            return response()->json([
                'approved' => false,
                'message' => __('pos::pos_return.pin.invalid'),
            ], 422);
        }

        // Ensure the return is in a state that can be approved
        if (! $return->canBeApproved()) {
            return response()->json([
                'approved' => false,
                'message' => __('pos::pos_return.pin.not_approvable'),
            ], 422);
        }

        $approved = $approveAction->execute($return, $manager);

        return response()->json([
            'approved' => true,
            'return_status' => $approved->status,
            'manager_name' => $manager->name,
        ]);
    }
}
