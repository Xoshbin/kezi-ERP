<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

/**
 * Tests for translation key consistency across modules.
 *
 * These tests ensure that translation keys used in code are properly defined
 * in all supported locales (en, ckb).
 */
test('all module translation keys used in filament code exist in locale files', function () {
    $modulesPath = base_path('packages/kezi');
    $this->assertDirectoryExists($modulesPath);

    // Supported locales
    $locales = ['en', 'ckb'];

    // Find all PHP files in Modules
    $phpFiles = File::allFiles($modulesPath);

    // Collect all translation key usages
    $translationUsages = [];
    $missingTranslations = [];

    foreach ($phpFiles as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            continue;
        }

        // Match translation key patterns like __('module::file.key') or __("module::file.key")
        // Pattern matches: __('namespace::file.key'), __('file.key'), trans('namespace::file.key')
        $patterns = [
            "/__\(\s*['\"](([a-z0-9_-]+)::)?([a-z0-9_-]+)\.([a-z0-9_\.-]+)['\"]\s*[,)]/i",
            "/trans\(\s*['\"](([a-z0-9_-]+)::)?([a-z0-9_-]+)\.([a-z0-9_\.-]+)['\"]\s*[,)]/i",
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $hasNamespace = ! empty($match[2]);
                    $namespace = $hasNamespace ? strtolower($match[2]) : null;
                    $fileName = $match[3];
                    $keyPath = $match[4];
                    $fullKey = $hasNamespace ? "$namespace::$fileName.$keyPath" : "$fileName.$keyPath";

                    $translationUsages[$fullKey] = [
                        'module' => $namespace,
                        'file' => $fileName,
                        'key' => $keyPath,
                        'source' => $file->getRelativePathname(),
                    ];
                }
            }
        }
    }

    $this->assertNotEmpty($translationUsages, 'No translation usages found. Is the regex correct?');

    foreach ($translationUsages as $fullKey => $usage) {
        $moduleName = $usage['module'];
        $fileName = $usage['file'];
        $keyPath = $usage['key'];

        foreach ($locales as $locale) {
            App::setLocale($locale);
            $translated = __($fullKey);

            // If the translation returns the key itself, it's missing (usually contains :: or is exactly the input)
            if ($translated === $fullKey || (is_string($translated) && str_contains($translated, '::'))) {
                // Try to find the file manually as a fallback to give better error message
                $foundFile = false;
                $moduleDir = null;
                if ($moduleName) {
                    $moduleMap = [
                        'quality_control' => 'quality-control',
                        'project_management' => 'project-management',
                        'qualitycontrol' => 'quality-control',
                        'projectmanagement' => 'project-management',
                        'accounting' => 'accounting',
                        'inventory' => 'inventory',
                        'purchase' => 'purchase',
                        'sales' => 'sales',
                        'foundation' => 'foundation',
                        'product' => 'product',
                        'hr' => 'hr',
                        'financial' => 'financial',
                        'maintenance' => 'maintenance',
                        'asset' => 'asset',
                        'crm' => 'crm',
                        'manufacturing' => 'manufacturing',
                        'pos' => 'pos',
                        'payroll' => 'payroll',
                        'budget' => 'budget',
                    ];
                    $moduleDir = $moduleMap[$moduleName] ?? $moduleName;
                    $translationFile = "$modulesPath/$moduleDir/resources/lang/$locale/$fileName.php";
                    $foundFile = File::exists($translationFile);
                }

                if ($moduleName && ! $foundFile) {
                    $missingTranslations[] = "[$locale] Missing translation file for '$fullKey' in source {$usage['source']}. Expected: packages/kezi/$moduleDir/resources/lang/$locale/$fileName.php";
                } else {
                    $missingTranslations[] = "[$locale] Missing or unresolved key '$fullKey' in source {$usage['source']}";
                }
            }
        }
    }

    // Sort and deduplicate
    $missingTranslations = array_unique($missingTranslations);
    sort($missingTranslations);

    $this->assertEmpty(
        $missingTranslations,
        "Found missing translations:\n".implode("\n", $missingTranslations)
    );
});

test('english and kurdish translation files have matching keys', function () {
    $modulesPath = base_path('packages/kezi');
    $this->assertDirectoryExists($modulesPath);

    $modules = File::directories($modulesPath);
    $keyMismatches = [];

    foreach ($modules as $modulePath) {
        $moduleName = basename($modulePath);
        $enLangPath = "$modulePath/resources/lang/en";
        $ckbLangPath = "$modulePath/resources/lang/ckb";

        // Skip if no translation directories
        if (! File::isDirectory($enLangPath) || ! File::isDirectory($ckbLangPath)) {
            continue;
        }

        $enFiles = File::files($enLangPath);

        foreach ($enFiles as $enFile) {
            $fileName = $enFile->getFilename();
            $ckbFile = "$ckbLangPath/$fileName";

            if (! File::exists($ckbFile)) {
                $keyMismatches[] = "[$moduleName] Missing Kurdish translation file: $fileName";

                continue;
            }

            // Compare keys
            $enTranslations = include $enFile->getRealPath();
            $ckbTranslations = include $ckbFile;

            $enKeys = flattenArrayKeys($enTranslations);
            $ckbKeys = flattenArrayKeys($ckbTranslations);

            $missingInCkb = array_diff($enKeys, $ckbKeys);
            $missingInEn = array_diff($ckbKeys, $enKeys);

            foreach ($missingInCkb as $key) {
                $keyMismatches[] = "[$moduleName::$fileName] Key '$key' exists in EN but missing in CKB";
            }

            foreach ($missingInEn as $key) {
                $keyMismatches[] = "[$moduleName::$fileName] Key '$key' exists in CKB but missing in EN";
            }
        }
    }

    $this->assertEmpty(
        $keyMismatches,
        "Found key mismatches between EN and CKB translations:\n".implode("\n", $keyMismatches)
    );
});

/**
 * Helper function to flatten nested array keys into dot notation.
 *
 * @param  array<string|int, mixed>  $array
 * @return list<string>
 */
function flattenArrayKeys(array $array, string $prefix = ''): array
{
    $keys = [];

    foreach ($array as $key => $value) {
        $keyStr = (string) $key;
        $fullKey = $prefix === '' ? $keyStr : "$prefix.$keyStr";

        if (is_array($value)) {
            $keys = array_merge($keys, flattenArrayKeys($value, $fullKey));
        } else {
            $keys[] = $fullKey;
        }
    }

    return $keys;
}
