<?php

return [
    'label' => 'دەستە',
    'plural_label' => 'دەستەکان',

    'sections' => [
        'basic_info' => 'زانیاری بنەڕەتی',
        'expiration' => 'بەسەرچوون',
    ],

    'fields' => [
        'lot_code' => 'کۆدی دەستە',
        'product' => 'بەرهەم',
        'expiration_date' => 'ڕێکەوتی بەسەرچوون',
        'expiration_date_help' => 'بەتاڵی بهێڵەرەوە ئەگەر بەرهەم بەسەرناچێت',
        'days_until_expiration' => 'ڕۆژ بۆ بەسەرچوون',
        'active' => 'چالاک',
        'stock_quants_count' => 'شوێنەکانی کۆگا',
        'created_at' => 'ڕێکەوتی دروستکردن',
    ],

    'filters' => [
        'product' => 'بەرهەم',
        'active' => 'چالاک',
        'expired' => 'بەسەرچووە',
        'expiring_soon' => 'زوو بەسەردەچێت (٣٠ ڕۆژ)',
        'no_expiration' => 'بەسەرناچێت',
    ],

    'no_expiration' => 'بەسەرناچێت',
    'expires_in_days' => 'بەسەردەچێت لە :days ڕۆژ',
    'expires_today' => 'ئەمڕۆ بەسەردەچێت',
    'expired_days_ago' => ':days ڕۆژ بەسەرچووە',
];
