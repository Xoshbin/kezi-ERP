<?php

return [
    // Labels
    'label' => 'دراو',
    'plural_label' => 'دراوەکان',

    // Fields
    'code' => 'کۆد',
    'name' => 'ناو',
    'symbol' => 'هێما',
    'exchange_rate' => 'نرخی گۆڕینەوە',
    'is_active' => 'چالاکە',
    'last_updated_at' => 'کۆتا نوێکردنەوە',
    'created_at' => 'کاتی دروستکردن',
    'updated_at' => 'کاتی نوێکردنەوە',

    // Exchange Rates
    'exchange_rates' => [
        'label' => 'نرخی گۆڕینەوە',
        'plural_label' => 'نرخەکانی گۆڕینەوە',
        'currency' => 'دراو',
        'rate' => 'نرخی گۆڕینەوە',
        'effective_date' => 'بەرواری کارکردن',
        'source' => 'سەرچاوە',
        'rate_helper' => 'نرخ بەراورد بە دراوی بنەڕەتی کۆمپانیا (١ دراوی بیانی = X دراوی بنەڕەتی)',
        'recent_filter' => 'نوێ (کۆتا ٣٠ ڕۆژ)',
        'sources' => [
            'manual' => 'تۆمارکردنی دەستی',
            'api' => 'هاوردەی API',
            'bank' => 'نرخی بانک',
            'central_bank' => 'بانکی ناوەندی',
            'seeder' => 'تۆخمکاری بنکەدراو',
        ],
    ],
];
