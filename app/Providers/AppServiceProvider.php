<?php

namespace App\Providers;

use App\Listeners\PostJournalEntry;
use App\Models\JournalEntry;
use App\Models\LockDate;
use App\Observers\JournalEntryObserver;
use App\Observers\LockDateObserver;
use Illuminate\Support\ServiceProvider;
use App\Livewire\Synthesizers\MoneySynth;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{

    protected $subscribe = [
        PostJournalEntry::class,
    ];
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::propertySynthesizer(MoneySynth::class);
        LockDate::observe(LockDateObserver::class);
        JournalEntry::observe(JournalEntryObserver::class);
    }
}
