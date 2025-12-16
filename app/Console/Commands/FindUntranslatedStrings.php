<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FindUntranslatedStrings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:find-missing
                            {--base=en : The base locale to compare against}
                            {--locales=* : Specific locales to check (leave empty for all)}
                            {--format=json : Output format for saved results (json, csv, md)}
                            {--no-save : Do not save results to file}
                            {--include-vendor : Include vendor translation files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find missing translation strings across different locales';

    protected string $baseLangPath;

    protected array $missingTranslations = [];

    protected int $totalMissing = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->baseLangPath = resource_path('lang');

        if (! File::isDirectory($this->baseLangPath)) {
            $this->error("Language directory not found at: {$this->baseLangPath}");

            return self::FAILURE;
        }

        $baseLocale = $this->option('base');
        $includeVendor = $this->option('include-vendor');

        // Get all available locales
        $availableLocales = $this->getAvailableLocales();

        if (empty($availableLocales)) {
            $this->error('No language directories found.');

            return self::FAILURE;
        }

        // Validate base locale
        if (! in_array($baseLocale, $availableLocales)) {
            $this->error("Base locale '{$baseLocale}' not found. Available locales: ".implode(', ', $availableLocales));

            return self::FAILURE;
        }

        // Get target locales
        $targetLocales = $this->option('locales');
        if (empty($targetLocales)) {
            $targetLocales = array_diff($availableLocales, [$baseLocale]);
        } else {
            // Validate target locales
            $invalidLocales = array_diff($targetLocales, $availableLocales);
            if (! empty($invalidLocales)) {
                $this->error('Invalid locales: '.implode(', ', $invalidLocales));

                return self::FAILURE;
            }
        }

        $this->info('Scanning translations...');
        $this->info("Base Locale: {$baseLocale}");
        $this->info('Target Locales: '.implode(', ', $targetLocales));
        $this->newLine();

        // Get base translations
        $baseTranslations = $this->loadTranslations($baseLocale, $includeVendor);

        if (empty($baseTranslations)) {
            $this->warn("No translations found for base locale '{$baseLocale}'");

            return self::FAILURE;
        }

        // Compare with each target locale
        foreach ($targetLocales as $locale) {
            $this->info("Checking locale: {$locale}");
            $targetTranslations = $this->loadTranslations($locale, $includeVendor);
            $missing = $this->findMissingKeys($baseTranslations, $targetTranslations, $locale);

            if (! empty($missing)) {
                $this->missingTranslations[$locale] = $missing;
            }
        }

        // Display results
        $this->displayResults();

        // Save results to file (unless --no-save is specified)
        if (! $this->option('no-save')) {
            $format = $this->option('format');
            $savedPath = $this->saveResults($format);

            if ($savedPath) {
                $this->newLine();
                $this->info("✓ Results saved to: {$savedPath}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Get all available locales from the lang directory.
     */
    protected function getAvailableLocales(): array
    {
        $directories = File::directories($this->baseLangPath);
        $locales = [];

        foreach ($directories as $dir) {
            $locale = basename($dir);
            // Skip vendor directory
            if ($locale !== 'vendor') {
                $locales[] = $locale;
            }
        }

        return $locales;
    }

    /**
     * Load all translations for a given locale.
     */
    protected function loadTranslations(string $locale, bool $includeVendor = false): array
    {
        $translations = [];
        $localePath = "{$this->baseLangPath}/{$locale}";

        // Load regular PHP translation files
        $files = File::glob("{$localePath}/*.php");
        foreach ($files as $file) {
            $key = basename($file, '.php');
            $translations[$key] = include $file;
        }

        // Load JSON translation file if exists
        $jsonFile = "{$this->baseLangPath}/{$locale}.json";
        if (File::exists($jsonFile)) {
            $jsonTranslations = json_decode(File::get($jsonFile), true);
            if ($jsonTranslations) {
                $translations['_json'] = $jsonTranslations;
            }
        }

        // Load vendor translations if requested
        if ($includeVendor) {
            $vendorPath = "{$this->baseLangPath}/vendor";
            if (File::isDirectory($vendorPath)) {
                $vendorDirs = File::directories($vendorPath);
                foreach ($vendorDirs as $vendorDir) {
                    $vendorName = basename($vendorDir);
                    $vendorLocalePath = "{$vendorDir}/{$locale}";
                    if (File::isDirectory($vendorLocalePath)) {
                        $vendorFiles = File::glob("{$vendorLocalePath}/*.php");
                        foreach ($vendorFiles as $file) {
                            $key = "vendor.{$vendorName}.".basename($file, '.php');
                            $translations[$key] = include $file;
                        }
                    }
                }
            }
        }

        return $translations;
    }

    /**
     * Find missing translation keys by comparing base and target translations.
     */
    protected function findMissingKeys(array $base, array $target, string $locale, string $prefix = ''): array
    {
        $missing = [];

        foreach ($base as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (! array_key_exists($key, $target)) {
                // Key doesn't exist at all
                $missing[] = [
                    'key' => $fullKey,
                    'type' => 'missing_key',
                    'base_value' => is_array($value) ? '[array]' : $value,
                ];
                $this->totalMissing++;
            } elseif (is_array($value) && is_array($target[$key])) {
                // Both are arrays, recurse
                $nestedMissing = $this->findMissingKeys($value, $target[$key], $locale, $fullKey);
                if (! empty($nestedMissing)) {
                    $missing = array_merge($missing, $nestedMissing);
                }
            } elseif (is_array($value) && ! is_array($target[$key])) {
                // Base is array but target is not
                $missing[] = [
                    'key' => $fullKey,
                    'type' => 'type_mismatch',
                    'base_value' => '[array]',
                    'target_value' => $target[$key],
                ];
                $this->totalMissing++;
            } elseif (! is_array($value) && is_array($target[$key])) {
                // Base is not array but target is
                $missing[] = [
                    'key' => $fullKey,
                    'type' => 'type_mismatch',
                    'base_value' => $value,
                    'target_value' => '[array]',
                ];
                $this->totalMissing++;
            } elseif (empty($target[$key]) && ! empty($value)) {
                // Value exists but is empty
                $missing[] = [
                    'key' => $fullKey,
                    'type' => 'empty_value',
                    'base_value' => $value,
                ];
                $this->totalMissing++;
            }
        }

        return $missing;
    }

    /**
     * Display the results in a formatted way.
     */
    protected function displayResults(): void
    {
        $this->newLine();

        if (empty($this->missingTranslations)) {
            $this->info('✓ No missing translations found!');

            return;
        }

        $this->error("✗ Found {$this->totalMissing} missing translations");
        $this->newLine();

        foreach ($this->missingTranslations as $locale => $missing) {
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->warn("Locale: {$locale} (".count($missing).' missing)');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            foreach ($missing as $item) {
                $icon = match ($item['type']) {
                    'missing_key' => '✗',
                    'empty_value' => '⚠',
                    'type_mismatch' => '⚡',
                    default => '•',
                };

                $this->line("  {$icon} <fg=yellow>{$item['key']}</>");
                $this->line("     Type: <fg=cyan>{$item['type']}</>");
                $this->line("     Base: <fg=green>{$item['base_value']}</>");

                if (isset($item['target_value'])) {
                    $this->line("     Target: <fg=red>{$item['target_value']}</>");
                }

                $this->newLine();
            }
        }

        // Summary
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('Summary:');
        $this->info("Total missing translations: {$this->totalMissing}");
        $this->info('Affected locales: '.implode(', ', array_keys($this->missingTranslations)));
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }

    /**
     * Save results to a file.
     */
    protected function saveResults(string $format): ?string
    {
        // Ensure translations directory exists
        $translationsDir = storage_path('app/translations');
        if (! File::isDirectory($translationsDir)) {
            File::makeDirectory($translationsDir, 0755, true);
        }

        $timestamp = date('Y-m-d_His');
        $fileName = "missing-translations-{$timestamp}.{$format}";
        $filePath = "{$translationsDir}/{$fileName}";

        if (empty($this->missingTranslations)) {
            // Save a "clean" report when no issues found
            if ($format === 'json') {
                File::put($filePath, json_encode([
                    'scanned_at' => now()->toIso8601String(),
                    'status' => 'clean',
                    'message' => 'No missing translations found',
                    'missing_translations' => [],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } elseif ($format === 'md') {
                $content = "# Translation Status Report\n\n";
                $content .= '**Scanned at:** '.now()->toDateTimeString()."\n\n";
                $content .= "✓ **Status:** All translations are up to date!\n\n";
                $content .= "_No missing translations found._\n";
                File::put($filePath, $content);
            } else {
                return null;
            }

            return $filePath;
        }

        if ($format === 'json') {
            $data = [
                'scanned_at' => now()->toIso8601String(),
                'total_missing' => $this->totalMissing,
                'affected_locales' => array_keys($this->missingTranslations),
                'missing_translations' => $this->missingTranslations,
            ];
            File::put($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } elseif ($format === 'csv') {
            $csv = fopen($filePath, 'w');
            fputcsv($csv, ['Locale', 'Key', 'Type', 'Base Value', 'Target Value']);

            foreach ($this->missingTranslations as $locale => $missing) {
                foreach ($missing as $item) {
                    fputcsv($csv, [
                        $locale,
                        $item['key'],
                        $item['type'],
                        $item['base_value'],
                        $item['target_value'] ?? '',
                    ]);
                }
            }

            fclose($csv);
        } elseif ($format === 'md') {
            $content = $this->generateMarkdownReport();
            File::put($filePath, $content);
        } else {
            $this->error("Unsupported format: {$format}. Supported: json, csv, md");

            return null;
        }

        return $filePath;
    }

    /**
     * Generate a markdown report of missing translations.
     */
    protected function generateMarkdownReport(): string
    {
        $content = "# Missing Translations Report\n\n";
        $content .= '**Generated:** '.now()->toDateTimeString()."\n\n";
        $content .= "**Total Missing:** {$this->totalMissing}\n\n";
        $content .= '**Affected Locales:** '.implode(', ', array_keys($this->missingTranslations))."\n\n";
        $content .= "---\n\n";

        foreach ($this->missingTranslations as $locale => $missing) {
            $count = count($missing);
            $content .= "## Locale: `{$locale}` ({$count} missing)\n\n";

            // Group by file for better organization
            $groupedByFile = [];
            foreach ($missing as $item) {
                $parts = explode('.', $item['key']);
                $file = $parts[0] ?? 'unknown';
                $groupedByFile[$file][] = $item;
            }

            foreach ($groupedByFile as $file => $items) {
                $content .= "### File: `{$file}.php`\n\n";
                $content .= "| Key | Type | Base Value |\n";
                $content .= "|-----|------|------------|\n";

                foreach ($items as $item) {
                    $icon = match ($item['type']) {
                        'missing_key' => '❌',
                        'empty_value' => '⚠️',
                        'type_mismatch' => '⚡',
                        default => '•',
                    };

                    $key = str_replace($file.'.', '', $item['key']);
                    $type = $item['type'];
                    $baseValue = str_replace('|', '\\|', $item['base_value']); // Escape pipes for markdown

                    $content .= "| {$icon} `{$key}` | {$type} | {$baseValue} |\n";
                }

                $content .= "\n";
            }

            $content .= "---\n\n";
        }

        $content .= "## Summary\n\n";
        $content .= "This report was generated to help you track and fix missing translations in your application.\n\n";
        $content .= "### Next Steps\n\n";
        $content .= "1. Review each missing translation key\n";
        $content .= "2. Add translations to the appropriate locale files\n";
        $content .= "3. Run the command again to verify all translations are added\n";

        return $content;
    }
}
