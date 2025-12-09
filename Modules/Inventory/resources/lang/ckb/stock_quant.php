<?php

return [
    'label' => 'بڕی کۆگا',
    'plural_label' => 'بڕەکانی کۆگا',

    'sections' => [
        'basic_info' => 'زانیاری سەرەتایی',
        'quantities' => 'بڕەکان',
    ],

    'fields' => [
        'id' => 'ناسنامە',
        'product' => 'بەرهەم',
        'location' => 'شوێن',
        'lot' => 'ژمارەی وەجبە',
        'quantity' => 'بڕی بەردەست', // Total quantity physically
        'reserved_quantity' => 'بڕی پارێزراو',
        'available_quantity' => 'بڕی بەکارهاتوو', // Available to sell
        'updated_at' => 'بەرواری نوێکردنەوە',
    ],

    'filters' => [
        'product' => 'بەرهەم',
        'location' => 'شوێن',
        'lot' => 'ژمارەی وەجبە',
        'low_stock' => 'کەمبوونی کۆگا',
        'out_of_stock' => 'نەمانی کۆگا',
        'with_reservations' => 'لەگەڵ پارێزراوەکان',
    ],

    'no_lot' => 'بێ وەجبە',

    'empty_state' => [
        'heading' => 'هیچ بڕێکی کۆگا نییە',
        'description' => 'هیچ بەرهەمێک لە کۆگاکەتدا تۆمار نەکراوە.',
    ],
];
