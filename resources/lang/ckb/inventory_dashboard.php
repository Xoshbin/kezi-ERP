<?php

return [
    // Navigation and Page Titles
    'navigation_label' => 'داشبۆرد',
    'title' => 'داشبۆردی کۆگا',
    'heading' => 'تێڕوانینی گشتی بۆ کۆگا',
    'subheading' => 'چاودێری کردنی ئەدای کۆگا و پێوەرە سەرەکییەکان',

    // Filters
    'filters' => [
        'date_from' => 'لە بەرواری',
        'date_to' => 'بۆ بەرواری',
        'location' => 'شوێن',
        'products' => 'بەرهەمەکان',
    ],

    // Stats Overview
    'stats' => [
        'total_value' => 'کۆی نرخی کۆگا',
        'total_value_description' => 'نرخی ئێستای هەموو کۆگاکان',
        
        'turnover_ratio' => 'ڕێژەی سووڕانەوەی کۆگا',
        'turnover_description' => 'ڕێژەی سووڕانەوەی ساڵانەی کۆگا',
        
        'low_stock' => 'کاڵای کەم لە کۆگا',
        'low_stock_description' => 'بەرهەمەکان کەمتر لە ئاستی کەمترین',
        
        'expiring_lots' => 'لۆتەکانی بەسەرچوو',
        'expiring_logs_description' => 'لۆتەکان کە لە ماوەی 30 ڕۆژدا بەسەردەچن',
    ],

    // Charts
    'charts' => [
        'inventory_value' => [
            'title' => 'ڕەوتی نرخی کۆگا',
            'description' => 'شوێنکەوتنی گۆڕانکارییەکانی نرخی کۆگا بە درێژایی کات',
            'dataset_label' => 'نرخی کۆگا',
        ],
        
        'turnover' => [
            'title' => 'وەرگرتن دژ بە گەیاندن',
            'description' => 'بەراوردکردنی هەفتانەی جووڵەکانی کۆگا',
            'receipts_label' => 'وەرگرتن',
            'deliveries_label' => 'گەیاندن',
        ],
        
        'aging' => [
            'title' => 'پیربوونی کۆگا',
            'description' => 'دابەشکردنی کۆگا بەپێی تەمەن',
            'quantity_label' => 'بڕ',
        ],
    ],

    // Quick Actions
    'quick_actions' => [
        'new_receipt' => [
            'title' => 'وەرگرتنی نوێ',
            'description' => 'تۆمارکردنی کۆگای هاتوو',
            'button' => 'دروستکردنی وەرگرتن',
        ],
        
        'new_delivery' => [
            'title' => 'گەیاندنی نوێ',
            'description' => 'تۆمارکردنی کۆگای چووە دەرەوە',
            'button' => 'دروستکردنی گەیاندن',
        ],
        
        'reports' => [
            'title' => 'بینینی ڕاپۆرتەکان',
            'description' => 'دەستڕاگەیشتن بە ڕاپۆرتە ورد و درێژەکانی کۆگا',
            'button' => 'بینینی ڕاپۆرتەکان',
        ],
    ],
];
