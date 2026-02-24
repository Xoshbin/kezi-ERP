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
        'is_negative_stock' => 'عەمباری نەرێنی (کەمی)',
        'updated_at' => 'دواین نوێکردنەوە',
    ],

    'discrepancy_label' => 'کەمی و جیاوازی عەمبار',
    'discrepancy_plural_label' => 'کەمی و جیاوازییەکانی عەمبار',

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

    'actions' => [
        'reconcile' => 'ڕێکخستنەوە (چارەسەرکردن)',
    ],
];
