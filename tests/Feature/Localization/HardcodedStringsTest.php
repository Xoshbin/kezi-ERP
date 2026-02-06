<?php

use Illuminate\Support\Facades\File;

/**
 * Tests for detecting hardcoded strings in Filament resources and pages.
 *
 * These tests ensure that user-facing text is properly internationalized
 * using the __() translation function instead of hardcoded strings.
 */

/**
 * Patterns that indicate hardcoded strings in Filament components.
 * These are method calls that typically accept user-facing text.
 * Each pattern captures the full string content for better reporting.
 *
 * @return list<array{pattern: string, context: string}>
 */
function getHardcodedStringPatterns(): array
{
    return [
        // Labels, titles, descriptions (require 2+ letters to avoid single-letter false positives)
        ['pattern' => '->label\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '->label()'],
        ['pattern' => '->title\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '->title()'],
        ['pattern' => '->description\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '->description()'],
        ['pattern' => '->heading\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '->heading()'],
        ['pattern' => '->helperText\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '->helperText()'],
        ['pattern' => '->placeholder\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '->placeholder()'],
        ['pattern' => '->hint\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '->hint()'],
        ['pattern' => '->tooltip\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '->tooltip()'],

        // Section/Tab names
        ['pattern' => 'Section::make\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => 'Section::make()'],
        ['pattern' => 'Tab::make\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => 'Tab::make()'],
        ['pattern' => 'Tabs::make\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => 'Tabs::make()'],
        ['pattern' => 'Wizard::make\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => 'Wizard::make()'],
        ['pattern' => 'Step::make\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => 'Step::make()'],
        ['pattern' => 'Fieldset::make\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => 'Fieldset::make()'],
        ['pattern' => 'Card::make\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => 'Card::make()'],

        // Action labels / modal strings
        ['pattern' => '->successNotificationTitle\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '->successNotificationTitle()'],
        ['pattern' => '->failureNotificationTitle\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '->failureNotificationTitle()'],
        ['pattern' => '->modalHeading\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '->modalHeading()'],
        ['pattern' => '->modalDescription\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '->modalDescription()'],
        ['pattern' => '->modalSubmitActionLabel\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '->modalSubmitActionLabel()'],
        ['pattern' => '->modalCancelActionLabel\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '->modalCancelActionLabel()'],

        // Notifications
        ['pattern' => '->sendNotification\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '->sendNotification()'],

        // Widget/Resource titles
        ['pattern' => 'protected static \?string \$heading\s*=\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '$heading property'],
        ['pattern' => 'protected static \?string \$title\s*=\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '$title property'],
        ['pattern' => 'protected static \?string \$navigationLabel\s*=\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '$navigationLabel property'],
        ['pattern' => 'protected static \?string \$modelLabel\s*=\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '$modelLabel property'],
        ['pattern' => 'protected static \?string \$pluralModelLabel\s*=\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '$pluralModelLabel property'],
        ['pattern' => 'protected static \?string \$navigationGroup\s*=\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '$navigationGroup property'],

        // Error/success messages
        ['pattern' => '->successMessage\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '->successMessage()'],
        ['pattern' => '->errorMessage\s*\(\s*[\'"]([A-Z][a-zA-Z0-9\s_-]{1,})[\'"]', 'context' => '->errorMessage()'],
    ];
}

/**
 * Patterns to exclude (false positives).
 *
 * @return list<string>
 */
function getExcludedPatterns(): array
{
    return [
        // CSS classes and framework identifiers
        '->label\s*\(\s*[\'"]heroicon-',
        '->label\s*\(\s*[\'"]fa-',
        '->label\s*\(\s*[\'"]icon-',

        // HTML attributes
        '->label\s*\(\s*[\'"]ID[\'"]',
        '->label\s*\(\s*[\'"]URL[\'"]',
        '->label\s*\(\s*[\'"]SKU[\'"]',  // Acronyms are acceptable

        // Technical identifiers (single words that are technical terms)
        '->label\s*\(\s*[\'"]UUID[\'"]',
        '->label\s*\(\s*[\'"]API[\'"]',
        '->label\s*\(\s*[\'"]PDF[\'"]',
    ];
}

/**
 * Directories/files to skip during scanning.
 *
 * @return list<string>
 */
function getExcludedPaths(): array
{
    return [
        'vendor',
        'node_modules',
        'storage',
        '.git',
        'resources/lang',  // Translation files themselves
        'tests',           // Test files may have intentional hardcoded strings
        'database/seeders',
        'database/factories',
    ];
}

test('filament resources and pages do not contain hardcoded user-facing strings', function () {
    $modulesPath = base_path('packages/kezi');
    $appPath = base_path('app');
    $this->assertDirectoryExists($modulesPath);

    $hardcodedStrings = [];
    $patterns = getHardcodedStringPatterns();
    $excludedPatterns = getExcludedPatterns();
    $excludedPaths = getExcludedPaths();

    // Scan Modules directory
    $phpFiles = File::allFiles($modulesPath);

    // Also scan app directory for Filament resources
    if (File::isDirectory($appPath)) {
        $phpFiles = array_merge($phpFiles, File::allFiles($appPath));
    }

    foreach ($phpFiles as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        // Skip excluded paths
        $relativePath = $file->getRelativePathname();
        $shouldSkip = false;
        foreach ($excludedPaths as $excludedPath) {
            if (str_contains($relativePath, $excludedPath)) {
                $shouldSkip = true;
                break;
            }
        }
        if ($shouldSkip) {
            continue;
        }

        // Only scan Filament-related files
        $fullPath = $file->getRealPath();
        if (! str_contains($fullPath, 'Filament')) {
            continue;
        }

        $content = file_get_contents($fullPath);
        if ($content === false) {
            continue;
        }

        foreach ($patterns as $patternData) {
            $pattern = $patternData['pattern'];
            $context = $patternData['context'];

            if (preg_match_all("/{$pattern}/m", $content, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $fullMatch = $match[0][0];
                    $offset = $match[0][1];
                    $capturedString = $match[1][0] ?? '';

                    // Check if this match should be excluded
                    $isExcluded = false;
                    foreach ($excludedPatterns as $excludedPattern) {
                        if (preg_match("/{$excludedPattern}/", $fullMatch)) {
                            $isExcluded = true;
                            break;
                        }
                    }

                    if ($isExcluded || empty($capturedString)) {
                        continue;
                    }

                    // Calculate line number
                    $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;

                    // Truncate long strings
                    $snippet = strlen($capturedString) > 50 ? substr($capturedString, 0, 50).'...' : $capturedString;

                    $hardcodedStrings[] = sprintf(
                        '%s:%d %s: "%s"',
                        $relativePath,
                        $lineNumber,
                        $context,
                        $snippet
                    );
                }
            }
        }
    }

    // Deduplicate and sort
    $hardcodedStrings = array_unique($hardcodedStrings);
    sort($hardcodedStrings);

    $this->assertEmpty(
        $hardcodedStrings,
        "Found potential hardcoded strings that should use __() translation:\n".implode("\n", $hardcodedStrings)
    );
});

test('blade templates in modules do not contain hardcoded user-facing text', function () {
    $modulesPath = base_path('packages/kezi');
    $this->assertDirectoryExists($modulesPath);

    $hardcodedStrings = [];

    // Patterns for hardcoded text in Blade templates
    $patterns = [
        // Direct text in common HTML elements (with capitalized first letter)
        '/<h[1-6][^>]*>\s*([A-Z][a-zA-Z\s]{3,})\s*<\/h[1-6]>/m',
        '/<button[^>]*>\s*([A-Z][a-zA-Z\s]{3,})\s*<\/button>/m',
        '/<label[^>]*>\s*([A-Z][a-zA-Z\s]{3,})\s*<\/label>/m',
        '/<th[^>]*>\s*([A-Z][a-zA-Z\s]{3,})\s*<\/th>/m',
        '/<title>\s*([A-Z][a-zA-Z\s]{3,})\s*<\/title>/m',

        // Blade component attributes with hardcoded strings
        '/:label="[\'"]([A-Z][a-zA-Z\s]{3,})[\'"]"/m',
        '/:title="[\'"]([A-Z][a-zA-Z\s]{3,})[\'"]"/m',
        '/:placeholder="[\'"]([A-Z][a-zA-Z\s]{3,})[\'"]"/m',
    ];

    // Patterns to exclude (false positives)
    $excludedPatterns = [
        '/Lorem ipsum/i',
        '/Example/i',
        '/DocType/i',
        '/HTML/i',
        '/Company/i',
    ];

    $bladeFiles = File::allFiles($modulesPath);

    foreach ($bladeFiles as $file) {
        if ($file->getExtension() !== 'php' || ! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        // Skip language/translation directories
        if (str_contains($file->getRelativePathname(), 'resources/lang')) {
            continue;
        }

        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            continue;
        }

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $fullMatch = $match[0][0];
                    $offset = $match[0][1];
                    $text = (string) $match[1][0];

                    // Check if this match should be excluded
                    $isExcluded = false;
                    foreach ($excludedPatterns as $excludedPattern) {
                        if (preg_match($excludedPattern, $text)) {
                            $isExcluded = true;
                            break;
                        }
                    }

                    if ($isExcluded) {
                        continue;
                    }

                    // Calculate line number
                    $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;

                    // Truncate long text
                    $snippet = strlen($text) > 40 ? substr($text, 0, 40).'...' : $text;

                    $hardcodedStrings[] = sprintf(
                        '%s:%d - "%s"',
                        $file->getRelativePathname(),
                        $lineNumber,
                        trim($snippet)
                    );
                }
            }
        }
    }

    // Deduplicate and sort
    $hardcodedStrings = array_unique($hardcodedStrings);
    sort($hardcodedStrings);

    $this->assertEmpty(
        $hardcodedStrings,
        "Found potential hardcoded strings in Blade templates that should use {{ __() }} translation:\n".implode("\n", $hardcodedStrings)
    );
});

test('filament resources have properly translated metadata labels', function () {
    $modulesPath = base_path('packages/kezi');
    $appPath = base_path('app');

    $phpFiles = File::allFiles($modulesPath);
    if (File::isDirectory($appPath)) {
        $phpFiles = array_merge($phpFiles, File::allFiles($appPath));
    }

    $failedTranslations = [];

    foreach ($phpFiles as $file) {
        if ($file->getExtension() !== 'php' || ! str_contains($file->getFilename(), 'Resource.php')) {
            continue;
        }

        $fullPath = $file->getRealPath();
        if (! str_contains($fullPath, 'Filament')) {
            continue;
        }

        // Determine namespace by reading the file content
        $content = file_get_contents($fullPath);
        if (! preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
            continue;
        }

        $namespace = $namespaceMatch[1];
        $className = $file->getBasename('.php');
        $fullClass = "\\{$namespace}\\{$className}";

        if (! class_exists($fullClass)) {
            continue;
        }

        $methodsToCheck = [
            'getNavigationGroup' => 'Navigation Group',
            'getNavigationLabel' => 'Navigation Label',
            'getModelLabel' => 'Model Label',
            'getPluralModelLabel' => 'Plural Model Label',
        ];

        foreach ($methodsToCheck as $method => $labelType) {
            if (! method_exists($fullClass, $method)) {
                continue;
            }

            // check English
            App::setLocale('en');
            $enValue = $fullClass::$method();

            // check Kurdish
            App::setLocale('ckb');
            $ckbValue = $fullClass::$method();

            if ($enValue === null) {
                continue;
            }

            // Check for missing translation strings (key returned as value)
            if (Str::contains((string) $enValue, '::')) {
                $failedTranslations[] = "Resource {$fullClass} has missing properties in English for {$labelType}: '{$enValue}'";

                continue;
            }

            // Check if values are identical (indicates hardcoded string or missing translation)
            if ($enValue === $ckbValue) {
                $failedTranslations[] = "Resource {$fullClass} seems to have untranslated {$labelType} (EN: '{$enValue}', CKB: '{$ckbValue}'). It might be hardcoded or missing ckb translation.";
            }
        }
    }

    $this->assertEmpty(
        $failedTranslations,
        "Found untranslated resource metadata labels:\n".implode("\n", $failedTranslations)
    );
});
