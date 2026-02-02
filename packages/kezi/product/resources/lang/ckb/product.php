<?php

return [
    // Labels
    'label' => 'بەرهەم',
    'plural_label' => 'بەرهەمەکان',
    'create' => 'دروستکردنی بەرهەم',

    // Basic Information
    'basic_information' => 'زانیاری بنەڕەتی',
    'basic_information_description' => 'وردەکارییەکانی بنەڕەتی بەرهەم بنووسە لەوانە ناو، SKU، و جۆر.',
    'company' => 'کۆمپانیا',
    'name' => 'ناو',
    'sku' => 'SKU',
    'sku_copied' => 'SKU کۆپی کرا بۆ کلیپبۆرد!',
    'description' => 'پێناسە',
    'type' => 'جۆر',

    // Pricing Information
    'pricing_information' => 'زانیاری نرخ',
    'pricing_information_description' => 'نرخی بنەڕەتی یەکە بۆ ئەم بەرهەمە دابنێ.',
    'unit_price' => 'نرخی یەکە',

    // Accounting Configuration
    'accounting_configuration' => 'ڕێکخستنی ژمێریاری',
    'accounting_configuration_description' => 'هەژماری بنەڕەتی داهات و خەرجی بۆ ئەم بەرهەمە ڕێکبخە.',
    'income_account' => 'هەژماری داهات',
    'expense_account' => 'هەژماری خەرجی',
    'purchase_tax' => 'باجی کڕین',

    // Inventory Management
    'inventory_management' => 'بەڕێوەبردنی کۆگا',
    'inventory_management_description' => 'شێوازی پێوانی کۆگا و ژمێریاری بۆ بەرهەمە هەڵگیراوەکان ڕێکبخە.',
    'inventory_valuation_method' => 'شێوازی پێوانی',
    'inventory_valuation_method_help' => 'شێوازی ژمارینی تێچووی کۆگا هەڵبژێرە (FIFO، LIFO، AVCO، یان نرخی ستاندارد).',
    'average_cost' => 'تێچووی ناوەند',
    'average_cost_help' => 'تێچووی ناوەندی ئێستای هەر یەکەیەک (بە شێوەیەکی خۆکارانە ژمێردەکرێت).',
    'default_inventory_account' => 'هەژماری کۆگا',
    'default_cogs_account' => 'هەژماری تێچووی کاڵا فرۆشراوەکان',
    'default_stock_input_account' => 'هەژماری هاتنەژوورەوەی کۆگا',
    'default_price_difference_account' => 'هەژماری جیاوازی نرخ',
    'lot_tracking_enabled' => 'چالاککردنی شوێنکەوتنی دەستە',
    'lot_tracking_enabled_help' => 'چالاککردنی شوێنکەوتنی دەستە/کۆمەڵ بۆ ئەم بەرهەمە بۆ شوێنکەوتنی ژمارە زنجیرەییەکان، دەستەکان، یان بەرواری بەسەرچوون.',

    // Stock Information
    'stock_moves' => 'جووڵەی کۆگا',
    'inventory_cost_layers' => 'چینەکانی تێچوو',
    'quantity_on_hand' => 'بڕی بەردەست',

    // Status
    'status' => 'دۆخ',
    'status_description' => 'کۆنترۆڵی ئەوە بکە کە ئایا ئەم بەرهەمە چالاکە و بۆ بەکارهێنان بەردەستە.',
    'is_active' => 'چالاکە',
    'is_active_help' => 'بەرهەمە ناچالاکەکان ناتوانن لە مامەڵە نوێیەکاندا بەکاربهێنرێن.',

    // Filters
    'all_products' => 'هەموو بەرهەمەکان',
    'active_products' => 'تەنها چالاکەکان',
    'inactive_products' => 'تەنها ناچالاکەکان',

    // Legacy fields (for backward compatibility)
    'company_id' => 'کۆمپانیا',
    'income_account_id' => 'هەژماری داهات',
    'expense_account_id' => 'هەژماری خەرجی',
    'sku_label' => 'SKU',
    'sku_column' => 'SKU',
    'created_at' => 'کاتی دروستبوون',
    'updated_at' => 'کاتی نوێکردنەوە',
    'deleted_at' => 'کاتی سڕینەوە',
    // Variants and Attributes
    'is_template' => 'نموونەیە؟',
    'is_template_help' => 'ئەمە چالاک بکە بۆ دروستکردنی جۆرەکانی کاڵا لە تایبەتمەندییەکانەوە.',
    'is_variant' => 'جۆرە؟',
    'variant_attributes' => 'تایبەتمەندییەکانی جۆر',
    'variant_attributes_description' => 'دیاریکردنی تایبەتمەندییەکان بۆ ئەم نموونەیە (وەک ڕەنگ، قەبارە)',
    'attributes' => 'تایبەتمەندییەکان',
    'attribute' => 'تایبەتمەندی',
    'values' => 'نرخەکان',
    'price' => 'نرخ',
    'on_hand' => 'بەردەست',
    'actions' => [
        'generate_variants' => 'دروستکردنی جۆرەکان',
        'generate_variants_success' => 'جۆرەکانی کاڵا بەسەرکەوتوویی دروستکران.',
    ],
    'attribute_types' => [
        'select' => 'دیاریکردن',
        'color' => 'ڕەنگ',
        'radio' => 'ڕادیۆۆ',
    ],
    'color_code' => 'کۆدی ڕەنگ',
    'navigation' => [
        'name' => 'بەرهەمەکان',
    ],
    'delete_existing_variants' => 'سڕینەوەی جۆرە بەردەستەکان',
    'delete_existing_variants_help' => 'ئەمە هەڵبژێرە بۆ لادانی جۆرە ئێستاکان پێش دروستکردنی جۆرە نوێیەکان. تەنها کار دەکات ئەگەر جۆرەکان هیچ مامەڵەیەکیان نەبێت.',
    'variant_generation' => [
        'options' => 'هەڵبژاردنەکان',
        'preview' => 'پیشاندان',
        'select_variants' => 'ئەو جۆرانە هەڵبژێرە کە دەتەوێت دروستیان بکەیت',
    ],
];
