<?php

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

    foreach ($phpFiles as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            continue;
        }

        // Match translation key patterns like __('module::file.key') or __("module::file.key")
        // Pattern: __('module::file.key.subkey...')
        if (preg_match_all("/__\(\s*['\"]([a-z]+)::([a-z_]+)\.([a-z_\.]+)['\"]\s*\)/i", $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fullKey = $match[1].'::'.$match[2].'.'.$match[3];
                $translationUsages[$fullKey] = [
                    'module' => strtolower($match[1]),
                    'file' => $match[2],
                    'key' => $match[3],
                    'source' => $file->getRelativePathname(),
                ];
            }
        }
    }

    $this->assertNotEmpty($translationUsages, 'No translation usages found. Is the regex correct?');

    $missingTranslations = [];

    foreach ($translationUsages as $fullKey => $usage) {
        $moduleName = $usage['module'];
        $fileName = $usage['file'];
        $keyPath = $usage['key'];

        // Map module name to its directory name
        $moduleMap = [
            'qualitycontrol' => 'quality-control',
            'projectmanagement' => 'project-management',
        ];
        $moduleDir = $moduleMap[$moduleName] ?? $moduleName;

        foreach ($locales as $locale) {
            $translationFile = "$modulesPath/$moduleDir/resources/lang/$locale/$fileName.php";

            if (! File::exists($translationFile)) {
                $missingTranslations[] = "[$locale] Missing translation file: $translationFile (key: $fullKey)";

                continue;
            }

            // Load the translation file
            $translations = include $translationFile;

            // Check if the key exists by traversing dot notation
            $keyParts = explode('.', $keyPath);
            $current = $translations;
            $found = true;

            foreach ($keyParts as $part) {
                if (! is_array($current) || ! array_key_exists($part, $current)) {
                    $found = false;
                    break;
                }
                $current = $current[$part];
            }

            if (! $found) {
                $missingTranslations[] = "[$locale] Missing key '$fullKey' in $translationFile";
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
