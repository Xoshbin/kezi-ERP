<?php

// resources/lang/ckb/enums.php

return [
    'account_type' => [
        'asset' => 'دارایی',
        'liability' => 'پابەندبوون',
        'equity' => 'سەرمایە',
        'income' => 'داهات',
        'expense' => 'خەرجی',
    ],

    'journal_entry_state' => [
        'draft' => 'ڕەشنووس',
        'posted' => 'پۆست کراو',
        'reversed' => 'پاشگەڕاوە',
    ],

    'journal_type' => [
        'sale' => 'فرۆشتن',
        'purchase' => 'کڕین',
        'bank' => 'بانک',
        'cash' => 'نەقد',
        'inventory' => 'کۆگا',
        'miscellaneous' => 'جۆراوجۆر',
    ],

    'lock_date_type' => [
        'tax_return_date' => 'قوڵفی گەڕانەوەی باج',
        'everything_date' => 'قوڵفی هەموو بەکارهێنەران',
        'hard_lock' => 'قوڵفی سەخت (نەگۆڕ)',
    ],

    'asset_status' => [
        'draft' => 'ڕەشنووس',
        'confirmed' => 'پشتڕاستکراوە',
        'depreciating' => 'دابەزین',
        'fully_depreciated' => 'بە تەواوی دابەزیوە',
        'sold' => 'فرۆشراوە',
    ],

    'depreciation_entry_status' => [
        'draft' => 'ڕەشنووس',
        'posted' => 'پۆست کراو',
    ],

    'depreciation_method' => [
        'straight_line' => 'هێڵی ڕاست',
        'declining' => 'بالانسی دابەزین',
    ],

    'stock_location_type' => [
        'internal' => 'ناوخۆیی',
        'customer' => 'کڕیار',
        'vendor' => 'فرۆشیار',
        'inventory_adjustment' => 'ڕێکخستنی کۆگا',
    ],

    'stock_move_status' => [
        'draft' => 'ڕەشنووس',
        'confirmed' => 'پشتڕاستکراوە',
        'done' => 'تەواو',
        'cancelled' => 'هەڵوەشاوە',
    ],

    'stock_move_type' => [
        'incoming' => 'هاتووە ژوورەوە',
        'outgoing' => 'چووەتە دەرەوە',
        'internal_transfer' => 'گواستنەوەی ناوخۆیی',
        'adjustment' => 'ڕێکخستن',
    ],

    'valuation_method' => [
        'fifo' => 'یەکەم هات، یەکەم چوو (FIFO)',
        'lifo' => 'دواتر هات، یەکەم چوو (LIFO)',
        'avco' => 'نرخی ناوەند (AVCO)',
        'standard_price' => 'نرخی ستاندارد',
    ],

    'partner_type' => [
        'customer' => 'کڕیار',
        'vendor' => 'فرۆشیار',
    ],

    'product_type' => [
        'product' => 'بەرهەم',
        'storable' => 'بەرهەمی هەڵگیراو',
        'consumable' => 'بەکارهێناو',
        'service' => 'خزمەتگوزاری',
    ],

    'vendor_bill_status' => [
        'draft' => 'ڕەشنووس',
        'posted' => 'پۆست کراو',
        'cancelled' => 'هەڵوەشاوە',
        'paid' => 'پارەدراو',
    ],

    'adjustment_document_type' => [
        'credit_note' => 'پسووڵەی گەڕاندنەوە',
        'debit_note' => 'پسووڵەی وەرگرتن',
        'miscellaneous' => 'هەمەجۆر',
    ],

    'adjustment_document_status' => [
        'draft' => 'ڕەشنووس',
        'posted' => 'پۆست کراو',
        'cancelled' => 'هەڵوەشاوە',
    ],

    'invoice_status' => [
        'draft' => 'ڕەشنووس',
        'posted' => 'پۆست کراو',
        'paid' => 'پارەدراو',
        'cancelled' => 'هەڵوەشاوە',
    ],

    'payment_type' => [
        'inbound' => 'هاتوو',
        'outbound' => 'چووە دەرەوە',
    ],

    'payment_status' => [
        'draft' => 'ڕەشنووس',
        'confirmed' => 'پشتڕاستکراوە',
        'reconciled' => 'ڕێکخراوە',
        'canceled' => 'هەڵوەشاوە',
    ],

    'tax_type' => [
        'sales' => 'فرۆشتن',
        'purchase' => 'کڕین',
        'both' => 'هەردووک',
    ],
];
