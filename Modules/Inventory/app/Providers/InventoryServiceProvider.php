<?php

namespace Modules\Inventory\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class InventoryServiceProvider extends ServiceProvider
{
    protected string $name = 'Inventory';

    protected string $nameLower = 'inventory';

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
        $this->loadMigrationsFrom(base_path('Modules/Inventory/database/migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Inventory\Console\Commands\RunReorderingSchedulerCommand::class,
            // \Modules\Inventory\Console\Commands\InventoryPerformanceAnalysis::class, // optional
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
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
            $this->mergeGlobalTranslations($langPath);
        } else {
            // Load translations from the module's resources/lang directory (Laravel Modules v12 best practice)
            $moduleLang = base_path('Modules/Inventory/resources/lang');
            $this->loadTranslationsFrom($moduleLang, $this->nameLower);
            $this->loadJsonTranslationsFrom($moduleLang);
            $this->mergeGlobalTranslations($moduleLang);
        }
    }

    /**
     * Merge module translations into global translator so keys like __('inventory::inventory_accounting.*') work
     */
    protected function mergeGlobalTranslations(string $baseLangPath): void
    {
        $translator = $this->app->make('translator');

        if (! is_dir($baseLangPath)) {
            return;
        }

        // Iterate locale directories (e.g., en, ar, ckb)
        foreach (scandir($baseLangPath) as $locale) {
            if ($locale === '.' || $locale === '..') {
                continue;
            }

            $localeDir = $baseLangPath.DIRECTORY_SEPARATOR.$locale;
            if (! is_dir($localeDir)) {
                continue;
            }

            foreach (glob($localeDir.DIRECTORY_SEPARATOR.'*.php') as $file) {
                $group = pathinfo($file, PATHINFO_FILENAME);
                $lines = require $file;
                if (is_array($lines)) {
                    // Flatten nested arrays into dot notation for Translator::addLines
                    $flattened = $this->flattenTranslationLines($group, $lines);
                    if (! empty($flattened)) {
                        $translator->addLines($flattened, $locale);
                    }
                }
            }
        }
    }

    /**
     * Flatten translation lines into dot-notated keys compatible with Translator::addLines
     */
    protected function flattenTranslationLines(string $group, array $lines, string $prefix = ''): array
    {
        $result = [];
        foreach ($lines as $key => $value) {
            $itemKey = ltrim($prefix.$key, '.');
            if (is_array($value)) {
                $result += $this->flattenTranslationLines($group, $value, $itemKey.'.');
            } else {
                $result["{$group}.{$itemKey}"] = $value;
            }
        }

        return $result;
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = base_path('Modules/Inventory/config');

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
        $sourcePath = base_path('Modules/Inventory/resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace('Modules\\Inventory\\View\\Components', $this->nameLower);
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
