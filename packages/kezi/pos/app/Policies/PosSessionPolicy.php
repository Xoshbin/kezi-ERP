<?php

namespace Kezi\Pos\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Kezi\Pos\Models\PosSession;

class PosSessionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_pos_session');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PosSession $session): bool
    {
        return $user->can('view_pos_session') && $this->belongsToUserCompany($user, $session);
    }

    /**
     * Determine whether the user can open a session.
     */
    public function open(User $user): bool
    {
        return $user->can('create_pos_session');
    }

    /**
     * Determine whether the user can close the session.
     */
    public function close(User $user, PosSession $session): bool
    {
        // Own session or manager permission
        if ($session->user_id === $user->id) {
            return true;
        }

        return $user->can('manage_pos_sessions') && $this->belongsToUserCompany($user, $session);
    }

    /**
     * Helper to verify model belongs to user's company
     */
    protected function belongsToUserCompany(User $user, PosSession $session): bool
    {
        return $user->companies()->where('companies.id', $session->company_id)->exists();
    }
}
