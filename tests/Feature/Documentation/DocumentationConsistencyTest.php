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
        $englishPath = "$baseDocsPath/$fullSlug.md";
        $kurdishPath = "$baseDocsPath/$fullSlug.ckb.md";

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
    $userGuidePath = base_path('docs/User Guide');
    $this->assertDirectoryExists($userGuidePath);

    $files = File::files($userGuidePath);
    $missingTranslations = [];

    foreach ($files as $file) {
        $filename = $file->getFilename();

        // Skip non-markdown files
        if ($file->getExtension() !== 'md') {
            continue;
        }

        // Skip translation files themselves (.ckb.md, .ar.md)
        if (Str::endsWith($filename, '.ckb.md') || Str::endsWith($filename, '.ar.md')) {
            continue;
        }

        // Construct expected Kurdish filename
        $kurdishFilename = Str::replaceLast('.md', '.ckb.md', $filename);
        $kurdishPath = $userGuidePath.'/'.$kurdishFilename;

        if (! File::exists($kurdishPath)) {
            $missingTranslations[] = "User Guide '$filename' is missing Kurdish translation: $kurdishFilename";
        }
    }

    $this->assertEmpty($missingTranslations, "Found untranslated User Guides:\n".implode("\n", $missingTranslations));
});
