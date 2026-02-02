<?php

return [
    // Labels
    'label' => 'چینی تێچووی کۆگا',
    'plural_label' => 'چینەکانی تێچووی کۆگا',
    'navigation_group' => 'Inventory', // Added this as it's common

    'sections' => [
        'basic_info' => 'زانیاری بنەڕەتی',
        'quantities_costs' => 'بڕ و تێچووەکان',
        'source' => 'زانیاری سەرچاوە',
    ],

    'fields' => [
        'id' => 'کۆد',
        'product' => 'بەرهەم',
        'purchase_date' => 'بەرواری کڕین',
        'quantity' => 'بڕی سەرەتایی',
        'remaining_quantity' => 'بڕی ماوە',
        'cost_per_unit' => 'تێچوو بۆ هەر یەکەیەک',
        'total_cost' => 'کۆی تێچوو',
        'remaining_cost' => 'تێچووی ماوە',
        'source_type' => 'جۆری سەرچاوە',
        'source_type_help' => 'جۆری ئەو بەڵگەنامەیەی کە ئەم چینەی تێچووی دروستکردووە',
        'source_id' => 'کۆدی سەرچاوە',
        'source_id_help' => 'کۆدی ئەو بەڵگەنامەیەی کە ئەم چینەی تێچووی دروستکردووە',
        'created_at' => 'کاتی دروستبوون',
    ],

    'filters' => [
        'product' => 'بەرهەم',
        'source_type' => 'جۆری سەرچاوە',
        'purchase_date_from' => 'بەرواری کڕین لە',
        'purchase_date_until' => 'بەرواری کڕین تا',
        'depleted' => 'تەواوبووە',
        'active' => 'چالاک',
    ],

    'source_types' => [
        'stock_move' => 'جوڵەی کۆگا',
        'vendor_bill' => 'پسووڵەی فرۆشیار',
        'inventory_adjustment' => 'ڕێکخستنی کۆگا',
    ],

    'purchase_date' => 'بەرواری کڕین',
    'quantity' => 'بڕی سەرەتایی',
    'remaining_quantity' => 'بڕی ماوە',
    'cost_per_unit' => 'تێچوو بۆ هەر یەکەیەک',
    'total_cost' => 'کۆی تێچوو',
    'created_at' => 'کاتی دروستبوون',

    // Legacy fields for compatibility
    'info' => 'زانیاری چینی تێچوو',
    'info_description' => 'چینەکانی تێچوو بە شێوەیەکی خۆکارانە بۆ بەرهەمەکانی FIFO و LIFO دروست دەکرێن. ئەوان تێچووی کڕینی کۆگا و بەکارهێنانی شوێندەکەونەوە.',
    'remaining_value' => 'بەهای ماوە',
    'source' => 'بەڵگەی سەرچاوە',
    'has_remaining_quantity' => 'بڕی ماوەی هەیە',
    'fully_consumed' => 'بە تەواوی بەکارهاتووە',
    'no_cost_layers' => 'هیچ چینێکی تێچوو نییە',
    'no_cost_layers_description' => 'چینەکانی تێچوون لێرە دەردەکەون کاتێک ئەم بەرهەمە شێوازی پێوانی FIFO یان LIFO بەکاردەهێنێت و جووڵەی کۆگای هەیە.',
];
