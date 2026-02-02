<?php

return [
    'immediate_payment' => 'پارەدانی دەستبەجێ',
    'net_days' => 'دوای :days ڕۆژ',
    'installment_description' => ':percentage% لە :days ڕۆژدا',
    'immediate' => 'دەستبەجێ',
    'end_of_month' => 'کۆتایی مانگ',
    'end_of_month_plus_days' => 'کۆتایی مانگ + :days ڕۆژ',
    'day_of_month' => ':day ی مانگ + :days ڕۆژ',
    'with_discount' => '(:percentage% داشکاندن ئەگەر لە :days ڕۆژدا بدرێت)',

    'types' => [
        'net' => 'ڕۆژەکان',
        'end_of_month' => 'کۆتایی مانگ',
        'day_of_month' => 'ڕۆژی مانگ',
        'immediate' => 'دەستبەجێ',
        'net_description' => 'پارەدان واجبە دوای ژمارەی دیاریکراوی ڕۆژ لە ڕێکەوتی بەڵگە',
        'end_of_month_description' => 'پارەدان واجبە لە کۆتایی مانگ کۆ ڕۆژە زیادەکان',
        'day_of_month_description' => 'پارەدان واجبە لە ڕۆژێکی دیاریکراوی مانگ',
        'immediate_description' => 'پارەدان واجبە دەستبەجێ دوای وەرگرتن',
    ],

    'common' => [
        'immediate' => 'پارەدانی دەستبەجێ',
        'net_15' => '١٥ ڕۆژ',
        'net_30' => '٣٠ ڕۆژ',
        'net_60' => '٦٠ ڕۆژ',
        'eom' => 'کۆتایی مانگ',
        'eom_plus_30' => 'کۆتایی مانگ + ٣٠',
    ],

    'fields' => [
        'name' => 'ناوی مەرجی پارەدان',
        'description' => 'وەسف',
        'is_active' => 'چالاک',
        'lines' => 'هێڵەکانی مەرجی پارەدان',
        'sequence' => 'ڕیزبەندی',
        'type' => 'جۆر',
        'days' => 'ڕۆژەکان',
        'percentage' => 'ڕێژە',
        'day_of_month' => 'ڕۆژی مانگ',
        'discount_percentage' => 'داشکاندن %',
        'discount_days' => 'ڕۆژەکانی داشکاندن',
    ],

    'actions' => [
        'create' => 'دروستکردنی مەرجی پارەدان',
        'edit' => 'دەستکاریکردنی مەرجی پارەدان',
        'delete' => 'سڕینەوەی مەرجی پارەدان',
        'add_line' => 'زیادکردنی هێڵ',
        'remove_line' => 'لابردنی هێڵ',
    ],

    'messages' => [
        'created' => 'مەرجی پارەدان بە سەرکەوتوویی دروستکرا.',
        'updated' => 'مەرجی پارەدان بە سەرکەوتوویی نوێکرایەوە.',
        'deleted' => 'مەرجی پارەدان بە سەرکەوتوویی سڕایەوە.',
        'cannot_delete_in_use' => 'ناکرێت مەرجێکی پارەدان بسڕدرێتەوە کە بەکاردێت.',
    ],

    'validation' => [
        'name_required' => 'ناوی مەرجی پارەدان پێویستە.',
        'percentage_sum' => 'کۆی ڕێژەکان دەبێت یەکسان بێت بە ١٠٠%.',
        'percentage_positive' => 'ڕێژە دەبێت ئەرێنی بێت.',
        'days_required' => 'ڕۆژەکان پێویستە بۆ ئەم جۆرە.',
        'day_of_month_required' => 'ڕۆژی مانگ پێویستە بۆ ئەم جۆرە.',
        'day_of_month_range' => 'ڕۆژی مانگ دەبێت لە نێوان ١ بۆ ٣١ بێت.',
    ],
];
