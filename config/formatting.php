<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Number Formatting Locale
    |--------------------------------------------------------------------------
    |
    | This value determines the locale used for number formatting throughout
    | the application using Laravel's Number utility class. This is separate
    | from the application locale and allows you to control number display
    | independently from translations.
    |
    | Set to 'en' to always use English numerals (0123456789) regardless
    | of the application language, or set to 'auto' to use the current
    | application locale for number formatting.
    |
    | Supported values: 'en', 'auto', or any valid locale string
    |
    */

    'number_locale' => env('NUMBER_FORMATTING_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Currency Formatting Locale
    |--------------------------------------------------------------------------
    |
    | This value determines the locale used for currency formatting throughout
    | the application using Laravel's Number utility class. This is separate
    | from the application locale and allows you to control currency display
    | independently from translations.
    |
    | Set to 'en' to always use English numerals for currency formatting,
    | or set to 'auto' to use the current application locale.
    |
    | Supported values: 'en', 'auto', or any valid locale string
    |
    */

    'currency_locale' => env('CURRENCY_FORMATTING_LOCALE', 'en'),

];
