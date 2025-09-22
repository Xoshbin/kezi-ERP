<?php

// resources/lang/ckb/enums.php

return [
    'account_type' => [
        'asset' => 'سامان',
        'liability' => 'قەرز',
        'equity' => 'خاوەندارێتی',
        'income' => 'داهات',
        'expense' => 'خەرجی',
    ],

    'journal_entry_state' => [
        'draft' => 'ڕەشنووس',
        'posted' => 'نێردراوە',
        'reversed' => 'پاشگەڕاوە',
    ],

    'journal_type' => [
        'sale' => 'پسووڵەکانی فرۆشتن',
        'purchase' => 'پسووڵەکانی کڕین',
        'bank' => 'بانک',
        'cash' => 'کاش',
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
        'posted' => 'نێردراوە',
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

    'cost_source' => [
        'vendor_bill' => 'پسوڵەی فرۆشیار',
        'average_cost' => 'ناوەندی تێچوون',
        'cost_layer' => 'چینی تێچوون',
        'unit_price' => 'نرخی یەکە',
        'manual' => 'تۆمارکردنی دەستی',
        'company_default' => 'بنەڕەتی کۆمپانیا',
    ],

    'partner_type' => [
        'customer' => 'کڕیار',
        'vendor' => 'فرۆشیار',
        'both' => 'هەردووک',
    ],

    'product_type' => [
        'product' => 'بەرهەم',
        'storable' => 'بەرهەمی هەڵگیراو',
        'consumable' => 'بەکارهێناو',
        'service' => 'خزمەتگوزاری',
    ],

    'vendor_bill_status' => [
        'draft' => 'ڕەشنووس',
        'posted' => 'نێردراوە',
        'cancelled' => 'هەڵوەشاوە',
        'paid' => 'پارەدراو',
    ],

    'adjustment_document_type' => [
        'credit_note' => 'پسووڵەی گەڕاندنەوە',
        'debit_note' => 'پسووڵەی وەرگرتنەوە',
        'miscellaneous' => 'هەمەجۆر',
    ],

    'adjustment_document_status' => [
        'draft' => 'ڕەشنووس',
        'posted' => 'نێردراوە',
        'cancelled' => 'هەڵوەشاوە',
    ],

    'invoice_status' => [
        'draft' => 'ڕەشنووس',
        'posted' => 'نێردراوە',
        'paid' => 'پارەدراو',
        'cancelled' => 'هەڵوەشاوە',
    ],

    'payment_type' => [
        'inbound' => 'هاتوو',
        'outbound' => 'چووە دەرەوە',
    ],

    'payment_status' => [
        'draft' => 'ڕەشنووس',
        'confirmed' => 'پشتڕاستکراوەتەوە',
        'reconciled' => 'هاوتاکراوە',
        'canceled' => 'هەڵوەشاندراوەتەوە',
    ],

    'payment_state' => [
        'not_paid' => 'پارەنەدراو',
        'partially_paid' => 'بەشێکی پارەدراوە',
        'paid' => 'پارەدراوە',
    ],

    'payment_method' => [
        'manual' => 'دەستی',
        'check' => 'چێک',
        'bank_transfer' => 'گواستنەوەی بانکی',
        'credit_card' => 'کرێدت کارد',
        'debit_card' => 'کارتی خەرج',
        'cash' => 'پارە',
        'wire_transfer' => 'گواستنەوەی تەل',
        'ach' => 'ACH',
        'sepa' => 'SEPA',
        'online_payment' => 'پارەدانی ئۆنلاین',
    ],

    'tax_type' => [
        'sales' => 'فرۆشتن',
        'purchase' => 'کڕین',
        'both' => 'هەردووک',
    ],

    'budget_type' => [
        'analytic' => 'شیکاری',
        'financial' => 'دارایی',
    ],

    'budget_status' => [
        'draft' => 'ڕەشنووس',
        'finalized' => 'کۆتایی هاتووە',
    ],
];
