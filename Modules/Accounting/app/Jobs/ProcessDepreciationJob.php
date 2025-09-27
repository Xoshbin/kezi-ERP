<?php

namespace Modules\Accounting\Jobs;

use App\Actions\Assets\PostDepreciationEntryAction;
use App\Models\DepreciationEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDepreciationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public DepreciationEntry $entry) {}

    public function handle(): void
    {
        // Get the first user associated with the company
        $userWithPivot = $this->entry->asset->company->users()->first();
        if (! $userWithPivot) {
            throw new \Exception('No user found for company');
        }

        // Get the actual User model without pivot data
        $user = \App\Models\User::find($userWithPivot->getKey());
        // Ensure we have a single User model, not a collection
        if ($user instanceof \Illuminate\Database\Eloquent\Collection) {
            $user = $user->first();
        }
        if (! $user) {
            throw new \Exception('User not found');
        }

        // By using app(), Laravel's service container will automatically resolve
        // the nested dependencies for both actions.
        app(PostDepreciationEntryAction::class)->execute($this->entry, $user);
    }
}
