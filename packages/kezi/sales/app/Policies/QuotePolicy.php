<?php

namespace Kezi\Sales\Policies;

use App\Models\User;
use Kezi\Sales\Enums\Sales\QuoteStatus;
use Kezi\Sales\Models\Quote;

class QuotePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Quote $quote): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Quote $quote): bool
    {
        return $quote->isEditable();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Quote $quote): bool
    {
        return $quote->status === QuoteStatus::Draft;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Quote $quote): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Quote $quote): bool
    {
        return false;
    }

    /**
     * Determine whether the user can accept the quote.
     */
    public function accept(User $user, Quote $quote): bool
    {
        return $quote->status->canBeAccepted();
    }

    /**
     * Determine whether the user can reject the quote.
     */
    public function reject(User $user, Quote $quote): bool
    {
        return $quote->status->canBeRejected();
    }

    /**
     * Determine whether the user can cancel the quote.
     */
    public function cancel(User $user, Quote $quote): bool
    {
        return $quote->status->canBeCancelled();
    }

    /**
     * Determine whether the user can convert the quote.
     */
    public function convert(User $user, Quote $quote): bool
    {
        return $quote->canBeConverted();
    }
}
