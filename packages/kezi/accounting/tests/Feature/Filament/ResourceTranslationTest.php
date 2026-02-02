<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

it('ensures all filament resources have properly translated metadata', function () {
    $resourcePath = __DIR__.'/../../../app/Filament/Clusters/Accounting/Resources';

    $files = (new Finder)->files()->in($resourcePath)->name('*Resource.php');

    expect(iterator_count($files))->toBeGreaterThan(0);

    foreach ($files as $file) {
        $namespace = 'Kezi\\Accounting\\Filament\\Clusters\\Accounting\\Resources';
        $relativePath = $file->getRelativePath();
        $className = $file->getBasename('.php');

        if ($relativePath) {
            $subNs = str_replace('/', '\\', $relativePath);
            $fullClass = "{$namespace}\\{$subNs}\\{$className}";
        } else {
            $fullClass = "{$namespace}\\{$className}";
        }

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
            if (Str::contains($enValue, '::')) {
                $this->fail("Resource {$fullClass} has missing properties in English for {$labelType}: '{$enValue}'");
            }

            // Check if values are identical (indicates hardcoded string or missing translation)
            // We strip white space and case to ensure minor diffs don't count as translated
            // But usually different languages should be vastly different.
            if ($enValue === $ckbValue) {
                // Ignore empty strings or common universal terms if any (rare for EN vs CKB)
                $this->fail("Resource {$fullClass} seems to have untranslated {$labelType} (EN: '{$enValue}', CKB: '{$ckbValue}'). It might be hardcoded or missing ckb translation.");
            }
        }
    }
});
