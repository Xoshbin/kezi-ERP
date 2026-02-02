<?php

namespace Kezi\Accounting\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AccountingServiceProvider extends ServiceProvider
{
    protected string $name = 'Accounting';

    protected string $nameLower = 'accounting';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerLivewireComponents();
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // Bind the JournalEntryCreatorContract to the concrete implementation
        $this->app->bind(
            \Kezi\Accounting\Contracts\JournalEntryCreatorContract::class,
            \Kezi\Accounting\Actions\Accounting\CreateJournalEntryAction::class
        );

        // Bind the InvoiceJournalEntryCreatorContract to the concrete implementation
        $this->app->bind(
            \Kezi\Accounting\Contracts\InvoiceJournalEntryCreatorContract::class,
            \Kezi\Accounting\Actions\Accounting\CreateJournalEntryForInvoiceAction::class
        );

        // Bind the VendorBillJournalEntryCreatorContract to the concrete implementation
        $this->app->bind(
            \Kezi\Accounting\Contracts\VendorBillJournalEntryCreatorContract::class,
            \Kezi\Accounting\Actions\Accounting\CreateJournalEntryForVendorBillAction::class
        );

        // Bind the AdjustmentJournalEntryCreatorContract to the concrete implementation
        $this->app->bind(
            \Kezi\Accounting\Contracts\AdjustmentJournalEntryCreatorContract::class,
            \Kezi\Accounting\Actions\Accounting\CreateJournalEntryForAdjustmentAction::class
        );
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Kezi\Accounting\Console\Commands\ProcessDepreciations::class,
            \Kezi\Accounting\Console\Commands\RevalueForeignCurrencyBalances::class,
            \Kezi\Accounting\Console\Commands\ProcessRecurringTransactionsCommand::class,
            \Kezi\Accounting\Console\Commands\ProcessDunningCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
            $schedule->command('accounting:process-recurring')->dailyAt('00:00');
            $schedule->command('accounting:process-dunning')->dailyAt('01:00');
        });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', $this->nameLower);
            $this->loadJsonTranslationsFrom(__DIR__.'/../../resources/lang');
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = __DIR__.'/../../config';

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = __DIR__.'/../../resources/views';

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace('Kezi\\Accounting\\View\\Components', $this->nameLower);
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('accounting.bank-transactions-table', \Kezi\Accounting\Livewire\Accounting\BankTransactionsTable::class);
        Livewire::component('accounting.system-payments-table', \Kezi\Accounting\Livewire\Accounting\SystemPaymentsTable::class);
        Livewire::component('accounting.bank-reconciliation-matcher', \Kezi\Accounting\Livewire\Accounting\BankReconciliationMatcher::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
