<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Limit
    |--------------------------------------------------------------------------
    |
    | The default maximum number of results to return for translatable search
    | queries. This can be overridden on a per-component basis.
    |
    */

    'default_limit' => 50,

    /*
    |--------------------------------------------------------------------------
    | Locale Resolution Strategy
    |--------------------------------------------------------------------------
    |
    | Defines the strategy for resolving available locales. The system will
    | try these sources in order until it finds a valid configuration.
    |
    | Available strategies:
    | - 'auto': Automatically detect from Filament plugin, then app config
    | - 'filament': Only use Filament Spatie Translatable plugin
    | - 'config': Only use application configuration
    | - 'manual': Use the manually specified locales below
    |
    */

    'locale_strategy' => env('TRANSLATABLE_SEARCH_LOCALE_STRATEGY', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Manual Locales
    |--------------------------------------------------------------------------
    |
    | When using the 'manual' locale strategy, these locales will be used
    | for translatable search functionality.
    |
    */

    'manual_locales' => ['en', 'ckb', 'ar'],

    /*
    |--------------------------------------------------------------------------
    | Configuration Keys
    |--------------------------------------------------------------------------
    |
    | Configuration keys to check when resolving locales from application
    | configuration. These are checked in order.
    |
    */

    'config_keys' => [
        'app.supported_locales',
        'app.locales',
        'translatable.locales',
        'filament-spatie-translatable.default_locales',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Whether to cache resolved locales to improve performance. The cache
    | will be cleared when the application is restarted.
    |
    */

    'cache_locales' => true,

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Database-specific settings for translatable search queries.
    |
    */

    'database' => [
        /*
        | Whether to use case-insensitive search by default
        */
        'case_insensitive' => true,

        /*
        | Custom JSON extraction methods for different database drivers
        | You can override these if you need custom query logic
        */
        'json_extraction' => [
            'sqlite' => 'LOWER(json_extract(`{field}`, "$.{locale}")) LIKE ?',
            'mysql' => 'LOWER(JSON_UNQUOTE(JSON_EXTRACT(`{field}`, "$.{locale}"))) LIKE ?',
            'pgsql' => 'LOWER(({field}->>\'{locale}\')) LIKE ?',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Component Defaults
    |--------------------------------------------------------------------------
    |
    | Default settings for TranslatableSelect components.
    |
    */

    'component_defaults' => [
        'label_field' => 'name',
        'search_limit' => 50,
        'searchable' => true,
    ],

];
