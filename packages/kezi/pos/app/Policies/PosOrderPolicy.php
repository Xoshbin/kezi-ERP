<?php

namespace Kezi\Pos\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Kezi\Pos\Models\PosOrder;

class PosOrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_pos_order');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PosOrder $order): bool
    {
        return $user->can('view_pos_order') && $this->belongsToUserCompany($user, $order);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_pos_order');
    }

    /**
     * Determine whether the user can sync orders.
     */
    public function syncOrders(User $user): bool
    {
        return $user->can('create_pos_order');
    }

    /**
     * Helper to verify model belongs to user's company
     */
    protected function belongsToUserCompany(User $user, PosOrder $order): bool
    {
        return $user->companies()->where('companies.id', $order->company_id)->exists();
    }
}
