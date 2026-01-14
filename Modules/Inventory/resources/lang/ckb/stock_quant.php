<?php

return [
    'label' => 'بڕی عەمبار',
    'plural_label' => 'بڕەکانی عەمبار',

    'sections' => [
        'basic_info' => 'زانیاری بنەڕەتی',
        'quantities' => 'بڕەکان',
    ],

    'fields' => [
        'id' => 'ناسنامە',
        'product' => 'بەرهەم',
        'location' => 'شوێن',
        'lot' => 'ژمارەی وەجبە',
        'quantity' => 'بڕ',
        'reserved_quantity' => 'بڕی گیراو',
        'available_quantity' => 'بڕی بەردەست',
        'updated_at' => 'دواین نوێکردنەوە',
    ],

    'filters' => [
        'product' => 'بەرهەم',
        'location' => 'شوێن',
        'lot' => 'ژمارەی وەجبە',
        'low_stock' => 'کەمبوونی عەمبار (≤ 10)',
        'out_of_stock' => 'عەمبار تەواوبوو',
        'with_reservations' => 'لەگەڵ گیراوەکان',
    ],

    'no_lot' => 'هیچ وەجبەیەک نییە',

    'empty_state' => [
        'heading' => 'هیچ بڕێکی عەمبار نەدۆزرایەوە',
        'description' => 'بڕەکانی عەمبار لێرە دەردەکەون کاتێک بەرهەمەکان لە شوێنەکاندا عەمبار دەکرێن.',
    ],
];
