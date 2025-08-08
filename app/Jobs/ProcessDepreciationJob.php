<?php

namespace App\Jobs;

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

    public function __construct(public DepreciationEntry $entry)
    {
    }

    public function handle(): void
    {
        $user = $this->entry->asset->company->users()->first();
        // By using app(), Laravel's service container will automatically resolve
        // the nested dependencies for both actions.
        app(PostDepreciationEntryAction::class)->execute($this->entry, $user);
    }
}
