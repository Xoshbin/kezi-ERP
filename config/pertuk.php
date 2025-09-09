<?php

return [
    // Root folder for documentation files. Designed to be multi-lingual in future.
    // Place markdown files directly under docs/ (e.g., docs/payments.md) or under
    // per-locale folders (e.g., docs/en/payments.md, docs/ar/payments.md).
    'root' => base_path('docs'),

    // Default sort order when front matter 'order' is missing.
    'default_order' => 1000,

    // Excluded files or folders (relative to root).
    'exclude' => [
        '.DS_Store',
        'README.md',
        'Developers'
    ],

    // Cache TTL (seconds) for parsed HTML & metadata.
    'cache_ttl' => 3600,

    // Enable or disable the documentation system
    'enabled' => true,

    // Route prefix for documentation
    'route_prefix' => 'docs',

    // Route middleware
    'middleware' => [],
];
