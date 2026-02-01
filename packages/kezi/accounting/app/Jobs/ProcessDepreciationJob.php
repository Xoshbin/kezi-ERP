<?php

namespace Kezi\Accounting\Jobs;

use App\Models\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kezi\Accounting\Actions\Assets\PostDepreciationEntryAction;
use Kezi\Accounting\Models\DepreciationEntry;

class ProcessDepreciationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public DepreciationEntry $entry) {}

    public function handle(): void
    {
        // Get the first user associated with the company
        $userWithPivot = $this->entry->asset->company->users()->first();
        if (! $userWithPivot) {
            throw new Exception('No user found for company');
        }

        // Get the actual User model without pivot data
        $user = User::find($userWithPivot->getKey());
        // Ensure we have a single User model, not a collection
        if ($user instanceof Collection) {
            $user = $user->first();
        }
        if (! $user) {
            throw new Exception('User not found');
        }

        // By using app(), Laravel's service container will automatically resolve
        // the nested dependencies for both actions.
        app(PostDepreciationEntryAction::class)->execute($this->entry, $user);
    }
}
