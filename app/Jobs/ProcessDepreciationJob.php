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
        $user = $this->entry->company->users()->first();
        (new PostDepreciationEntryAction(new \App\Actions\Accounting\CreateJournalEntryForDepreciationAction()))->execute($this->entry, $user);
    }
}
