<?php

return [
    // Root folder for documentation files. Designed to be multi-lingual in future.
    // Place markdown files directly under docs/ (e.g., docs/payments.md) or under
    // per-locale folders (e.g., docs/en/payments.md, docs/ar/payments.md).
    'root' => base_path('docs'),

    // Default sort order when front matter 'order' is missing.
    'default_order' => 1000,

    // Excluded files or folders (relative to root) for file listing
    'exclude_directories' => [
        '.DS_Store',
        'README.md',
        'Developers',
    ],

    // Excluded version directories
    'exclude_versions' => [
        '.DS_Store',
        'README.md',
        'Developers',
    ],

    // Cache TTL (seconds) for parsed HTML & metadata.
    'cache_ttl' => 3600,

    // Enable or disable the documentation system
    'enabled' => true,

    // Route prefix for documentation
    'route_prefix' => 'docs',

    // Route middleware
    'middleware' => ['web'],

    // Localization settings
    'supported_locales' => ['en', 'ckb', 'ar'],
    'default_locale' => 'en',
    'rtl_locales' => ['ar', 'ckb'],
    'locale_labels' => [
        'en' => 'English',
        'ckb' => 'کوردی (سۆرانی)',
        'ar' => 'العربية',
    ],

    // GitHub Repo & Branch for "Edit on GitHub" links
    'github_repo' => env('PERTUK_GITHUB_REPO', 'username/repo'), // @phpstan-ignore-line
    'github_branch' => env('PERTUK_GITHUB_BRANCH', 'main'), // @phpstan-ignore-line

];
