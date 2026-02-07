<?php

return [
    'singular' => 'کۆمپانیا',
    'plural' => 'کۆمپانیاکان',
    'name' => 'ناو',
    'address' => 'ناونیشان',
    'tax_id' => 'ژمارەی باج',
    'currency_id' => 'پارە',
    'fiscal_country' => 'وڵاتی دارایی',
    'parent_company_id' => 'کۆمپانیای دایک',
    'default_accounts_payable' => 'هەژماری پێدانی بنەڕەت',
    'default_tax_receivable' => 'باجی وەرگرتنی بنەڕەت',
    'default_purchase_journal' => 'پەرتووکی ڕۆژانەی کڕینی بنەڕەت',
    'default_accounts_receivable' => 'هەژماری وەرگرتنی بنەڕەت',
    'default_sales_discount_account' => 'هەژماری داشکاندنی فرۆشتنی بنەڕەت',
    'default_tax_account' => 'هەژماری باجی بنەڕەت',
    'default_sales_journal' => 'پەرتووکی ڕۆژانەی فرۆشتنی بنەڕەت',
    'default_depreciation_journal' => 'پەرتووکی ڕۆژانەی داخورانی بنەڕەت',
    'default_bank_account' => 'هەژماری بانکی بنەڕەت',
    'default_outstanding_receipts_account' => 'هەژماری پسوولە وەرنەگیراوەکانی بنەڕەت',
    'created_at' => 'کاتی دروستبوون',
    'updated_at' => 'کاتی نوێکردنەوە',

    'accounts' => [
        'title' => 'هەژمارەکان',
        'code' => 'کۆد',
        'name' => 'ناو',
        'type' => 'جۆر',
        'is_deprecated' => 'بەسەرچوو',
    ],

    'users' => [
        'title' => 'بەکارهێنەران',
        'name' => 'ناو',
        'email' => 'ئیمەیڵ',
        'email_verified_at' => 'کاتی سەلماندنی ئیمەیڵ',
        'password' => 'وشەی نهێنی',
    ],

    // Sections
    'section' => [
        'details' => 'وردەکاری کۆمپانیا',
        'defaults' => 'ڕێکخستنە بنەڕەتییەکانی کۆمپانیا',
    ],

    'enable_reconciliation' => 'چالاککردنی هاوتاکردن',
    'enable_reconciliation_help' => 'چالاککردنی تایبەتمەندی هاوتاکردن بۆ ئەم کۆمپانیایە. ئەگەر ناچالاک بێت، هەموو تایبەتمەندییەکانی هاوتاکردن دەشاردرێنەوە.',

    'industry_type' => 'جۆری پیشەسازی',
    'inventory_accounting_mode' => 'شێوازی ژمێریاری کۆگا',
    'industries' => [
        'generic' => 'کار و باری گشتی',
        'retail' => 'فرۆشتنی تاک / POS',
        'manufacturing' => 'بەرهەمهێنان / MRP',
        'services' => 'خزمەتگوزارییە پیشەییەکان',
    ],

    'wizard' => [
        'identity' => 'ناسنامە',
        'identity_desc' => 'زانیارییە سەرەکییەکانی کۆمپانیاکەت پێمان بڵێ.',
        'foundation' => 'بناغە',
        'foundation_desc' => 'پارەی سەرەکی و بنکەی دارایی دیاری بکە.',
        'profile' => 'پڕۆفایلی کار',
        'profile_desc' => 'چ جۆرە کارێک بەڕێوە دەبەیت؟',
        'customization' => 'تایبەتمەندکردن',
        'customization_desc' => 'خەریکە تەواو دەبین! دوایین بژاردەکان.',
        'seed_sample_data' => 'دانانی زانیاری نموونەیی',
        'seed_sample_data_help' => 'ئەمە هەڵبژێرە بۆ پڕکردنەوەی کۆمپانیاکەت بە کڕیار، فرۆشیار و کاڵای نموونەیی بۆ تاقیکردنەوە.',
    ],
];
