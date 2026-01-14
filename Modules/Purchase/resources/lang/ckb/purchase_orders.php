<?php

return [
    'label' => 'داواکاری کڕین',
    'plural_label' => 'داواکارییەکانی کڕین',

    'navigation' => [
        'label' => 'داواکارییەکانی کڕین',
        'group' => 'کڕین',
    ],

    'sections' => [
        'basic_info' => 'زانیاری سەرەتایی',
        'vendor_details' => 'وردەکارییەکانی فرۆشیار',
        'delivery_info' => 'زانیاری گەیاندن',
        'line_items' => 'بەندەکان',
        'line_items_description' => 'بەرهەم و خزمەتگوزاری زیاد بکە بۆ ئەم داواکارییە',
        'notes' => 'تێبینی و مەرجەکان',
        'totals' => 'کۆی گشتی',
    ],

    'fields' => [
        'po_number' => 'ژمارەی داواکاری',
        'status' => 'دۆخ',
        'po_date' => 'بەرواری داواکاری',
        'reference' => 'سەرچاوە',
        'vendor' => 'فرۆشیار',
        'currency' => 'دراو',
        'expected_delivery_date' => 'بەرواری گەیشتنی پێشبینیکراو',
        'delivery_location' => 'شوێنی گەیاندن',
        'notes' => 'تێبینیەکان',
        'terms_and_conditions' => 'مەرج و ڕێنماییەکان',
        'total_amount' => 'کۆی گشتی',
        'total_tax' => 'کۆی باج',
        'created_by' => 'دروستکراوە لەلایەن',
        'created_at' => 'بەرواری دروستکردن',
        // Line item fields
        'lines' => 'بەندەکان',
        'product' => 'بەرهەم',
        'description' => 'وەسف',
        'quantity' => 'بڕ',
        'unit_price' => 'نرخی یەکە',
        'tax' => 'باج',
        'billing_status' => 'دۆخی پسوڵە',
    ],

    'help' => [
        'po_number' => 'بە شێوەی خۆکار دروست دەکرێت',
        'reference' => 'سەرچاوەی ناوخۆیی یان ژمارەی ئۆفەری فرۆشیار',
        'terms_and_conditions' => 'مەرجی ستاندارد بۆ ئەم داواکارییە',
        'status_can_create_bill' => 'دەتوانرێت پسوڵەی فرۆشیار دروست بکرێت لەم داواکارییە.',
        'status_cannot_create_bill' => 'دۆخەکە بگۆڕە بۆ پەسەندکراو یان وەرگرتن بۆ ئەوەی بتوانیت پسوڵە دروست بکەیت.',
        'status_bills_already_exist' => 'پسوڵەی فرۆشیار پێشتر دروستکراوە بۆ ئەم داواکارییە.',
        'status_forward_only' => 'دۆخەکە تەنها دەتوانرێت بۆ پێشەوە بگۆڕدرێت.',
    ],

    'actions' => [
        'confirm' => 'پەسەندکردن',
        'cancel' => 'هەڵوەشاندنەوە',
    ],

    'notifications' => [
        'confirmed' => 'داواکاری کڕین بە سەرکەوتوویی پەسەندکرا',
        'cancelled' => 'داواکاری کڕین بە سەرکەوتوویی هەڵوەشێنرایەوە',
        'update_not_allowed' => 'نوێکردنەوە ڕێگەپێنەدراوە',
    ],

    'status' => [
        // Pre-commitment phase
        'rfq' => 'داواکاری بۆ نرخق',
        'rfq_sent' => 'داواکاری نرخ نێردرا',

        // Commitment phase
        'draft' => 'ڕەشنووس',
        'sent' => 'نێردرا',
        'confirmed' => 'پەسەندکرا',

        // Fulfillment phase
        'to_receive' => 'بۆ وەرگرتن',
        'partially_received' => 'بەشێکی وەرگیراوە',
        'fully_received' => 'بە تەواوی وەرگیراوە',

        // Billing phase
        'to_bill' => 'بۆ پسوڵەکردن',
        'partially_billed' => 'بەشێکی پسوڵەکراوە',
        'fully_billed' => 'بە تەواوی پسوڵەکراوە',

        // Final states
        'done' => 'تەواو',
        'cancelled' => 'هەڵوەشێنراوە',
    ],

    'billing_status' => [
        'not_billed' => 'پسوڵە نەکراوە',
        'billed' => 'پسوڵە کراوە',
        'multiple_bills' => ':count پسوڵە',
    ],
];
