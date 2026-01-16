<?php

return [
    // Labels
    'label' => 'Currency',
    'plural_label' => 'Currencies',

    // Fields
    'code' => 'Code',
    'name' => 'Name',
    'symbol' => 'Symbol',
    'exchange_rate' => 'Exchange Rate',
    'is_active' => 'Is Active',
    'last_updated_at' => 'Last Updated At',
    'created' => 'Created',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',

    // Exchange Rates
    'exchange_rates' => [
        'label' => 'Exchange Rate',
        'plural_label' => 'Exchange Rates',
        'currency' => 'Currency',
        'rate' => 'Exchange Rate',
        'effective_date' => 'Effective Date',
        'source' => 'Source',
        'rate_helper' => 'Rate relative to company base currency (1 foreign currency = X base currency)',
        'recent_filter' => 'Recent (Last 30 days)',
        'sources' => [
            'manual' => 'Manual Entry',
            'api' => 'API Import',
            'bank' => 'Bank Rate',
            'central_bank' => 'Central Bank',
            'seeder' => 'Database Seeder',
        ],
    ],

    // Section
    'basic_information' => 'Basic Information',
];
