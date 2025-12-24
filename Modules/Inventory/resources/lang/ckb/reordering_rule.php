<?php

return [
    'label' => 'یاسای فەرمانی نوێ',
    'plural_label' => 'یاساکانی فەرمانی نوێ',
    'create' => 'زیادکردنی یاسای فەرمانی نوێ',

    'sections' => [
        'basic_info' => 'زانیاری بنەڕەتی',
        'quantities' => 'بڕەکان',
        'timing' => 'کاتبەندی',
    ],

    'fields' => [
        'product' => 'بەرهەم',
        'location' => 'شوێن',
        'route' => 'ڕێگا',
        'min_qty' => 'کەمترین بڕ',
        'min_qty_help' => 'کاتێک کۆگا لەم ئاستە کەمتر بوو فەرمانی نوێ بکە',
        'max_qty' => 'زۆرترین بڕ',
        'max_qty_help' => 'بڕی ئامانج بۆ فەرمانی نوێ',
        'safety_stock' => 'کۆگای سەلامەتی',
        'safety_stock_help' => 'ئاستی کۆگای کتوپڕ بۆ فەرمانی پەیتاو',
        'multiple' => 'لێکدان',
        'multiple_help' => 'بڕی فەرمان دەبێت لێکدانی ئەم نرخە بێت',
        'lead_time_days' => 'کاتی چاوەڕوان (ڕۆژ)',
        'lead_time_days_help' => 'کاتی گەیاندنی چاوەڕوانکراو بە ڕۆژ',
        'active' => 'چالاک',
        'current_stock' => 'کۆگای ئێستا',
        'status' => 'دۆخ',
        'updated_at' => 'کۆتا نوێکردنەوە',
    ],

    'filters' => [
        'product' => 'بەرهەم',
        'location' => 'شوێن',
        'route' => 'ڕێگا',
        'active' => 'چالاک',
        'needs_reorder' => 'پێویستی بە فەرمانی نوێ',
        'urgent' => 'پەیتاو',
    ],

    'status' => [
        'inactive' => 'ناچالاک',
        'urgent' => 'پەیتاو',
        'reorder_needed' => 'پێویستی بە فەرمانی نوێ',
        'ok' => 'باشە',
    ],

    'days' => 'ڕۆژ',
];
