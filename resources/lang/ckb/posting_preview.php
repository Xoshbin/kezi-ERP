<?php

return [
    'preview_posting' => 'پێشبینینی تۆمارکردن',
    'posting_preview' => 'پێشبینینی تۆماری ژمێریاری',
    'export_preview_csv' => 'هەناردەی پێشبینین (CSV)',
    'export_preview_pdf' => 'هەناردەی پێشبینین (PDF)',

    'errors_title' => 'هەڵەکان',

    'links' => [
        'fix_in_accounts' => 'چاكسازی لە هەژمارەکان',
        'fix_input_tax' => 'چاكسازی باجى هاوردە',
        'open_company' => 'کردنەوەی کۆمپانیا',
        'open_product' => 'کردنەوەی کاڵا',
        'open_assets' => 'کردنەوەی سامان',
        'open_accounts' => 'کردنەوەی هەژمارەکان',
        'open_taxes' => 'کردنەوەی باجەکان',
    ],

    'table' => [
        'account' => 'هەژمار',
        'description' => 'وەسف',
        'debit' => 'دەیبیت',
        'credit' => 'کرێدیت',
        'totals' => 'کۆی گشتی',
        'debits' => 'دەیبیتەکان',
        'credits' => 'کرێدیتەکان',
        'balanced' => 'هاوتراز',
        'unbalanced' => 'ناهاوتراز',
    ],

    'pdf' => [
        'vendor_bill_heading' => 'پێشبینینی تۆماری قەرزەکانی فرۆشیار',
        'invoice_heading' => 'پێشبینینی تۆماری پسوولە',
        'adjustment_heading' => 'پێشبینینی تۆماری چاکسازی',
    ],

    'lines' => [
        'inventory' => 'کۆگا: ',
        'asset' => 'سامان: ',
        'input_tax' => 'باژی هاوردە: ',
        'revenue' => 'داھات: ',
        'output_tax' => 'باژی دەرچوون: ',
        'ap' => 'قەرزدار',
        'ar' => 'قەرزدار',
        'sales_discount' => 'داشكانی فرۆشتن/دژە-داھات',
        'tax_payable' => 'باجی دراو',
    ],

    'errors' => [
        'ap_account_missing' => 'هەژماری قەرزداری بنەڕەتی بۆ کۆمپانیا دیاری نەکراوە.',
        'purchase_journal_missing' => 'پەرتووکی ڕۆژانەی کڕین دیاری نەکراوە بۆ کۆمپانیا.',
        'inventory_account_missing' => 'کالا بە ناسنامەی :product_id هەژماری کۆگای نییە.',
        'asset_category_invalid' => 'هاوپۆلی سامان هەڵەیە لە هێڵێکی قەرزنامە.',
        'input_tax_missing' => 'هەژماری باجی هاوردە بوونی نییە لەگەڵ ئەوەی هێڵەکان باج پێدان.',

        'ar_account_missing' => 'هەژماری قەرزدار بنەڕەتی بۆ کۆمپانیا دیاری نەکراوە.',
        'sales_journal_missing' => 'پەرتووکی ڕۆژانەی فرۆشتن بۆ کۆمپانیا دیاری نەکراوە.',
        'income_account_missing' => 'هێڵێک لە پسوولە هەژماری داھات نییە.',
        'tax_account_missing' => 'باجە هەڵبژێردراو هەژماری باج نییە.',

        'sales_discount_missing' => 'هەژماری داشكانی فرۆشتن بنەڕەتی دیاری نەکراوە.',
        'tax_payable_missing' => 'هەژماری باجی دراو بنەڕەتی دیاری نەکراوە.',
    ],
];
