<?php

namespace App\Providers;

use Modules\Accounting\Listeners\PostJournalEntry;
use Modules\Foundation\Livewire\Synthesizers\MoneySynth;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @var array<int, class-string>
     */
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
    }
}
