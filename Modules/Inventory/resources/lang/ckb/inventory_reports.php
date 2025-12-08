<?php

return [
    'valuation' => [
        'navigation_label' => 'بەهای کۆگا',
        'title' => 'ڕاپۆرتی بەهای کۆگا',
        'heading' => 'ڕاپۆرتی بەهای کۆگا',

        'filters' => [
            'title' => 'فلتەرەکانی ڕاپۆرت',
            'as_of_date' => 'لە بەرواری',
            'products' => 'بەرهەمەکان',
            'include_reconciliation' => 'لەگەڵ هاوتاکردنی لێجەری گشتی',
            'valuation_method' => 'ڕێگای خەمڵاندن',
        ],

        'summary' => [
            'total_value' => 'کۆی بەهای کۆگا',
            'total_quantity' => 'کۆی بڕ',
            'product_count' => 'بەرهەمەکان',
            'as_of_date' => 'لە بەرواری',
        ],

        'reconciliation' => [
            'title' => 'هاوتاکردنی لێجەری گشتی',
            'gl_balance' => 'باڵانسی هەژماری لێجەری گشتی',
            'calculated_value' => 'بەهای ئەژمارکراو',
            'difference' => 'جیاوازی',
            'reconciled' => 'هاوتاکراو',
            'not_reconciled' => 'هاوتانەکراو',
        ],

        'table' => [
            'title' => 'وردەکارییەکانی بەهای بەرهەم',
            'product' => 'بەرهەم',
            'valuation_method' => 'ڕێگا',
            'quantity' => 'بڕ',
            'unit_cost' => 'تێچووی یەکە',
            'total_value' => 'کۆی بەها',
            'cost_layers' => 'چینەکانی تێچوو',
        ],

        'actions' => [
            'export' => 'هەناردەکردن',
            'refresh' => 'نوێکردنەوە',
            'view_cost_layers' => 'بینینی چینەکانی تێچوو',
        ],

        'cost_layers_modal' => [
            'title' => 'چینەکانی تێچوو',
            'purchase_date' => 'بەرواری کڕین',
            'quantity' => 'بڕ',
            'cost_per_unit' => 'تێچوو بۆ هەر یەکەیەک',
            'total_value' => 'کۆی بەها',
            'total' => 'کۆ',
            'weighted_avg' => 'تێکڕای کێشکراو',
            'no_layers' => 'هیچ چینێکی تێچوو نییە',
            'no_layers_description' => 'ئەم بەرهەمە ڕێگای AVCO بەکاردەهێنێت یان هیچ جوڵەیەکی کۆگای نییە.',
        ],

        'export_started' => 'هەناردەکردن دەستی پێکرد بە سەرکەوتوویی.',
        'no_data' => 'هیچ داتایەکی کۆگا نەدۆزرایەوە',
        'no_data_description' => 'هیچ بەرهەمێک تۆماری نییە بۆ ئەو پێوەرانەی دیاریکراون.',
    ],

    'aging' => [
        'navigation_label' => 'تەمەنی کۆگا',
        'title' => 'ڕاپۆرتی تەمەنی کۆگا',
        'heading' => 'ڕاپۆرتی تەمەنی کۆگا',

        'filters' => [
            'title' => 'فلتەرەکانی ڕاپۆرت',
            'products' => 'بەرهەمەکان',
            'locations' => 'شوێنەکان',
            'include_expiration' => 'لەگەڵ شیکاری بەسەرچوون',
            'expiration_warning_days' => 'ڕۆژانی ئاگادارکردنەوەی بەسەرچوون',
        ],

        'summary' => [
            'total_value' => 'کۆی بەهای کۆگا',
            'total_quantity' => 'کۆی بڕ',
            'average_age' => 'تێکڕای تەمەن (ڕۆژ)',
            'expiring_soon' => 'بەمنزیکانە بەسەردەچێت',
        ],

        'buckets' => [
            'title' => 'دابەشبوونی تەمەن',
            'age_range' => 'مەودای تەمەن',
            'quantity' => 'بڕ',
            'value' => 'بەها',
            'percentage' => 'ڕێژە',
            'products' => 'بەرهەمەکان',
            'total' => 'کۆ',
        ],

        'expiration' => [
            'title' => 'لوتە بەسەرچووەکان',
            'lot_code' => 'کۆدی لوت',
            'product' => 'بەرهەم',
            'expiration_date' => 'بەرواری بەسەرچوون',
            'days_until_expiration' => 'ڕۆژانی ماوە بۆ بەسەرچوون',
            'quantity_on_hand' => 'بڕی بەردەست',
        ],

        'days' => 'ڕۆژ',
        'days_ago' => 'ڕۆژ لەمەوبەر',
        'expired' => 'بەسەرچوو',

        'export_confirmation' => 'هەناردەکردنی ڕاپۆرتی تەمەنی کۆگا',
        'export_description' => 'ئەمە پەڕگەیەکی CSV دروست دەکات کە شیکاری تەمەنی کۆگای تێدایە.',

        'actions' => [
            'export' => 'هەناردەکردن',
            'refresh' => 'نوێکردنەوە',
        ],
        'export_started' => 'هەناردەکردن دەستی پێکرد بە سەرکەوتوویی.',
        'export_failed' => 'هەناردەکردن سەرکەوتوو نەبوو',
        'no_data_to_export' => 'هیچ داتایەک نییە بۆ هەناردەکردن',
        'no_data' => 'هیچ داتایەکی تەمەن نەدۆزرایەوە',
        'no_data_description' => 'هیچ کۆگایەک نەدۆزرایەوە بۆ ئەو پێوەرانەی دیاریکراون.',
    ],

    'turnover' => [
        'navigation_label' => 'هەڵگەڕانەوەی کۆگا',
        'title' => 'ڕاپۆرتی هەڵگەڕانەوەی کۆگا',
        'heading' => 'ڕاپۆرتی هەڵگەڕانەوەی کۆگا',

        'filters' => [
            'title' => 'فلتەرەکانی ڕاپۆرت',
            'start_date' => 'بەرواری دەستپێک',
            'end_date' => 'بەرواری کۆتایی',
            'products' => 'بەرهەمەکان',
        ],

        'summary' => [
            'total_cogs' => 'کۆی تێچووی کاڵای فرۆشراو',
            'average_inventory' => 'تێکڕای بەهای کۆگا',
            'turnover_ratio' => 'ڕێژەی هەڵگەڕانەوە',
            'days_sales_inventory' => 'ڕۆژانی فرۆشتن لە کۆگا',
        ],

        'analysis' => [
            'title' => 'شیکاری هەڵگەڕانەوە',
            'excellent' => 'نایاب (>12x)',
            'good' => 'باش (6-12x)',
            'average' => 'مامناوەند (3-6x)',
            'poor' => 'خراپ (<3x)',
            'ratio_explanation' => 'کۆگاکەت :ratio جار هەڵدەگەڕێتەوە لەم ماوەیەدا.',
        ],

        'benchmarks' => [
            'excellent' => 'کۆگا زیاتر لە ١٢ جار لە ساڵێکدا هەڵدەگەڕێتەوە',
            'good' => 'کۆگا ٦-١٢ جار لە ساڵێکدا هەڵدەگەڕێتەوە',
            'average' => 'کۆگا ٣-٦ جار لە ساڵێکدا هەڵدەگەڕێتەوە',
            'poor' => 'کۆگا کەمتر لە ٣ جار لە ساڵێکدا هەڵدەگەڕێتەوە',
        ],

        'period_info' => [
            'title' => 'زانیاری ماوە',
            'start_date' => 'بەرواری دەستپێک',
            'end_date' => 'بەرواری کۆتایی',
            'period_length' => 'درێژی ماوە',
        ],

        'days' => 'ڕۆژ',
        'annualized' => 'ساڵانە',
        'actions' => [
            'export' => 'هەناردەکردن',
            'refresh' => 'نوێکردنەوە',
        ],
        'export_started' => 'هەناردەکردن دەستی پێکرد بە سەرکەوتوویی.',
        'export_failed' => 'هەناردەکردن سەرکەوتوو نەبوو',
        'no_data_to_export' => 'هیچ داتایەک نییە بۆ هەناردەکردن',
        'no_data' => 'هیچ داتایەکی هەڵگەڕانەوە نەدۆزرایەوە',
        'no_data_description' => 'هیچ تێچووی کاڵای فرۆشراو یان جوڵەی کۆگا نەدۆزرایەوە بۆ ئەو ماوەیەی دیاریکراوە.',
    ],

    'lot_trace' => [
        'navigation_label' => 'شوێنپێهەڵگرتنی لوت',
        'title' => 'ڕاپۆرتی شوێنپێهەڵگرتنی لوت',
        'heading' => 'ڕاپۆرتی شوێنپێهەڵگرتنی لوت',

        'filters' => [
            'title' => 'پێوەرەکانی گەڕان',
            'product' => 'بەرهەم',
            'lot' => 'لوت',
        ],

        'summary' => [
            'title' => 'کورختەی لوت',
            'lot_code' => 'کۆدی لوت',
            'product' => 'بەرهەم',
            'expiration_date' => 'بەرواری بەسەرچوون',
            'current_quantity' => 'بڕی ئێستا',
            'total_value' => 'کۆی بەها',
        ],

        'movements' => [
            'title' => 'مێژووی جوڵە',
            'date' => 'بەروار',
            'type' => 'جۆر',
            'quantity' => 'بڕ',
            'from_location' => 'لە شوێنی',
            'to_location' => 'بۆ شوێنی',
            'reference' => 'ژمارەی سەرچاوە',
            'journal_entry' => 'قیودی ڕۆژنامە',
            'valuation_amount' => 'بڕی خەمڵێنراو',
            'incoming' => 'جوڵەی هاتوو',
            'outgoing' => 'جوڵەی دەرچوو',
            'internal' => 'جوڵەی ناوخۆیی',
            'count' => 'جوڵەکان',
        ],

        'actions' => [
            'export' => 'هەناردەکردن',
            'refresh' => 'نوێکردنەوە',
        ],

        'no_expiration' => 'بەرواری بەسەرچوونی نییە',
        'export_started' => 'هەناردەکردن دەستی پێکرد بە سەرکەوتوویی.',
        'export_failed' => 'هەناردەکردن سەرکەوتوو نەبوو',
        'no_data_to_export' => 'هیچ داتایەک نییە بۆ هەناردەکردن',
        'no_selection' => 'بەرهەم و لوت دیاری بکە',
        'no_selection_description' => 'تکایە بەرهەم و لوتێک دیاری بکە بۆ بینینی زانیاری شوێنپێهەڵگرتن.',
        'no_movements' => 'هیچ جوڵەیەک نەدۆزرایەوە',
        'no_movements_description' => 'ئەم لوتە هیچ جوڵەیەکی تۆمارکراوی نییە لە سیستەمدا.',
    ],

    'reorder' => [
        'navigation_label' => 'دۆخی داواکردنەوە',
        'title' => 'ڕاپۆرتی دۆخی داواکردنەوە',
        'heading' => 'ڕاپۆرتی دۆخی داواکردنەوە',

        'filters' => [
            'title' => 'فلتەرەکانی ڕاپۆرت',
            'products' => 'بەرهەمەکان',
            'locations' => 'شوێنەکان',
            'include_suggested_orders' => 'لەگەڵ داواکارییە پێشنیارکراوەکان',
            'include_overstock' => 'لەگەڵ کاڵای زۆر لە کۆگا',
        ],

        'summary' => [
            'critical' => 'کاڵای هەستیار',
            'low_stock' => 'کەم لە کۆگا',
            'suggested' => 'داواکاری پێشنیارکراو',
            'overstock' => 'زۆر لە کۆگا',
            'suggested_value' => 'بەهای پێشنیارکراو',
        ],

        'alerts' => [
            'critical_title' => 'ئاگادارکردنەوەی کۆگای هەستیار',
            'critical_description' => ':count بەرهەم زۆر کەمن و پێویستیان بە ئاوڕ لێدانەوەی بەپەلە هەیە.',
        ],

        'table' => [
            'title' => 'دۆخی داواکردنەوە',
            'product' => 'بەرهەم',
            'location' => 'شوێن',
            'current_quantity' => 'بڕی ئێستا',
            'min_quantity' => 'کەمترین بڕ',
            'max_quantity' => 'زۆرترین بڕ',
            'suggested_quantity' => 'بڕی پێشنیارکراو',
            'status' => 'دۆخ',
            'estimated_cost' => 'تێچووی خەمڵێنراو',
        ],

        'actions' => [
            'export' => 'هەناردەکردن',
            'refresh' => 'نوێکردنەوە',
        ],

        'export_started' => 'هەناردەکردن دەستی پێکرد بە سەرکەوتوویی.',
        'export_failed' => 'هەناردەکردن سەرکەوتوو نەبوو',
        'no_data_to_export' => 'هیچ داتایەک نییە بۆ هەناردەکردن',
        'no_data' => 'هیچ داتایەکی داواکردنەوە نەدۆزرایەوە',
        'no_data_description' => 'هیچ بەرهەمێک بە ڕێسای داواکردنەوە نەدۆزرایەوە بۆ ئەو پێوەرانەی دیاریکراون.',
    ],
];
