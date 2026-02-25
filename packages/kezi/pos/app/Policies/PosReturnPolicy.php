<?php

namespace Kezi\Pos\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Kezi\Pos\Enums\PosReturnStatus;
use Kezi\Pos\Models\PosReturn;

class PosReturnPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_pos_return');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PosReturn $return): bool
    {
        return $user->can('view_pos_return') && $this->belongsToUserCompany($user, $return);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_pos_return');
    }

    /**
     * Determine whether the user can submit the model.
     */
    public function submit(User $user, PosReturn $return): bool
    {
        return ($user->can('create_pos_return') || $user->can('update_pos_return'))
            && $return->status === PosReturnStatus::Draft
            && $this->belongsToUserCompany($user, $return);
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, PosReturn $return): bool
    {
        return $user->can('approve_pos_return')
            && $return->status === PosReturnStatus::PendingApproval
            && $this->belongsToUserCompany($user, $return);
    }

    /**
     * Determine whether the user can reject the model.
     */
    public function reject(User $user, PosReturn $return): bool
    {
        return $user->can('approve_pos_return')
            && $return->status === PosReturnStatus::PendingApproval
            && $this->belongsToUserCompany($user, $return);
    }

    /**
     * Determine whether the user can process the model.
     */
    public function process(User $user, PosReturn $return): bool
    {
        return $user->can('process_pos_return')
            && $return->status === PosReturnStatus::Approved
            && $this->belongsToUserCompany($user, $return);
    }

    /**
     * Helper to verify model belongs to user's company
     */
    protected function belongsToUserCompany(User $user, PosReturn $return): bool
    {
        return $user->companies()->where('companies.id', $return->company_id)->exists();
    }
}
