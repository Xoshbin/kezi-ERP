<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\Foundation\Filament\Actions\DocsAction;

test('all docs action slugs resolve to existing files', function () {
    $modulesPath = base_path('Modules');
    $this->assertDirectoryExists($modulesPath, "Modules directory not found at $modulesPath");

    $phpFiles = File::allFiles($modulesPath);
    $slugsFound = [];

    foreach ($phpFiles as $file) {
        // Only scan PHP files
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $content = file_get_contents($file->getRealPath());

        // Regex to find DocsAction::make('slug')
        // This handles single quotes, double quotes, and optional spaces
        if (preg_match_all("/DocsAction::make\(\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $matches)) {
            foreach ($matches[1] as $slug) {
                $slugsFound[] = $slug;
            }
        }
    }

    $slugsFound = array_unique($slugsFound);
    $this->assertNotEmpty($slugsFound, 'No DocsAction usages found. Is the regex correct?');

    $missingFiles = [];

    foreach ($slugsFound as $slug) {
        $docsActionClass = new ReflectionClass(DocsAction::class);
        $mappingMethod = $docsActionClass->getMethod('mapSlugToDocumentationPath');
        $mappingMethod->setAccessible(true);

        $fullSlug = $mappingMethod->invoke(null, $slug);

        $baseDocsPath = base_path('docs');
        $englishPath = "$baseDocsPath/en/$fullSlug.md";
        $kurdishPath = "$baseDocsPath/ckb/$fullSlug.md";

        if (! File::exists($englishPath)) {
            $missingFiles[] = "Slug '$slug' maps to '$fullSlug' but ENGLISH file matches not found: $englishPath";
        }

        if (! File::exists($kurdishPath)) {
            $missingFiles[] = "Slug '$slug' maps to '$fullSlug' but KURDISH file matches not found: $kurdishPath";
        }
    }

    $this->assertEmpty($missingFiles, "Found documentation inconsistencies:\n".implode("\n", $missingFiles));
});

test('all user guides have translations', function () {
    $enDocsPath = base_path('docs/en');
    $this->assertDirectoryExists($enDocsPath);

    $files = File::allFiles($enDocsPath);
    $missingTranslations = [];

    foreach ($files as $file) {
        $filename = $file->getRelativePathname(); // e.g., explanation/understanding-sales-orders.md

        // Skip non-markdown files
        if ($file->getExtension() !== 'md') {
            continue;
        }

        // Construct expected Kurdish path
        $kurdishPath = base_path("docs/ckb/$filename");

        if (! File::exists($kurdishPath)) {
            $missingTranslations[] = "English doc '$filename' is missing Kurdish translation at: $kurdishPath";
        }
    }

    $this->assertEmpty($missingTranslations, "Found untranslated User Guides:\n".implode("\n", $missingTranslations));
});

test('every feature and report has a user guide link', function () {
    $modulesPath = base_path('Modules');
    $this->assertDirectoryExists($modulesPath);

    $phpFiles = File::allFiles($modulesPath);
    $missingDocs = [];

    foreach ($phpFiles as $file) {
        $path = $file->getRelativePathname();

        // Only look at Filament Pages/Resources directories
        if (! str_contains($path, '/Filament/') || ! str_contains($path, '/Pages/')) {
            continue;
        }

        $content = File::get($file->getRealPath());

        // Skip non-class files or traits/interfaces
        if (! preg_match('/class\s+\w+/', $content)) {
            continue;
        }

        // Skip abstract classes
        if (Str::contains($content, 'abstract class')) {
            continue;
        }

        // Identify "Entry Point" classes:
        // 1. Resource List Pages (ListRecords, ManageRecords)
        // 2. Standalone Pages (extending Page, but NOT inside a Resources sub-directory as a side-page)
        $isResourceEntryPoint = Str::contains($content, 'extends ListRecords') ||
                                Str::contains($content, 'extends ManageRecords');

        $isStandalonePage = Str::contains($content, 'extends Page') &&
                            ! str_contains($path, '/Resources/');

        if (! $isResourceEntryPoint && ! $isStandalonePage) {
            continue;
        }

        // Check for DocsAction::make
        if (! preg_match('/(?<!\/\/ )DocsAction::make/', $content)) {
            $missingDocs[] = $path;
        }
    }

    $this->assertEmpty($missingDocs, 'The following Feature Entry Points (List Pages or Reports) are missing a DocsAction documentation link:'.PHP_EOL.PHP_EOL.implode(PHP_EOL, $missingDocs));
});
