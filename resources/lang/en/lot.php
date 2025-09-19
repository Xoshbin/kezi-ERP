<?php

return [
    'label' => 'Lot',
    'plural_label' => 'Lots',

    'sections' => [
        'basic_info' => 'Basic Information',
        'expiration' => 'Expiration',
    ],

    'fields' => [
        'lot_code' => 'Lot Code',
        'product' => 'Product',
        'expiration_date' => 'Expiration Date',
        'expiration_date_help' => 'Leave empty if the product does not expire',
        'days_until_expiration' => 'Days Until Expiration',
        'active' => 'Active',
        'stock_quants_count' => 'Stock Locations',
        'created_at' => 'Created At',
    ],

    'filters' => [
        'product' => 'Product',
        'active' => 'Active',
        'expired' => 'Expired',
        'expiring_soon' => 'Expiring Soon (30 days)',
        'no_expiration' => 'No Expiration Date',
    ],

    'no_expiration' => 'No expiration',
    'expires_in_days' => 'Expires in :days days',
    'expires_today' => 'Expires today',
    'expired_days_ago' => 'Expired :days days ago',
];
