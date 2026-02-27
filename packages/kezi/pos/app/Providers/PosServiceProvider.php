<?php

namespace Kezi\Pos\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosReturn;
use Kezi\Pos\Models\PosSession;
use Kezi\Pos\Policies\PosOrderPolicy;
use Kezi\Pos\Policies\PosReturnPolicy;
use Kezi\Pos\Policies\PosSessionPolicy;

class PosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(PosOrder::class, PosOrderPolicy::class);
        Gate::policy(PosSession::class, PosSessionPolicy::class);
        Gate::policy(PosReturn::class, PosReturnPolicy::class);

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'pos');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'pos');

        \Illuminate\Support\Facades\Route::middleware('api')
            ->prefix('api/pos')
            ->name('api.pos.')
            ->group(__DIR__.'/../../routes/api.php');

        \Illuminate\Support\Facades\Route::group([], __DIR__.'/../../routes/web.php');
    }
}
