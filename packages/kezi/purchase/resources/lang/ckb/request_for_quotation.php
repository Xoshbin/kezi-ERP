<?php

return [
    'label' => 'داواکاری نرخدان',
    'plural_label' => 'داواکارییەکانی نرخدان',
    'navigation_label' => 'داواکارییەکانی نرخدان',
    'fields' => [
        'rfq_number' => 'ژمارەی داواکاری',
        'vendor' => 'فرۆشیار',
        'company' => 'کۆمپانیا',
        'rfq_date' => 'بەرواری داواکاری',
        'valid_until' => 'بەسەرچوون',
        'currency' => 'دراو',
        'exchange_rate' => 'نرخی ئاڵوگۆڕ',
        'status' => 'دۆخ',
        'subtotal' => 'کۆی لاوەکی',
        'tax_total' => 'کۆی باج',
        'total' => 'کۆی گشتی',
        'date' => 'بەروار',
        'bid_notes' => 'تێبینییەکانی بەشداربوون',
        'vendor_reference' => 'سەرچاوەی فرۆشیار',
        'notes' => 'تێبینیەکان',
    ],
    'sections' => [
        'general' => 'زانیاری گشتی',
        'basic_info' => 'زانیاری سەرەتایی',
        'vendor_info' => 'وردەکارییەکانی فرۆشیار',
        'line_items' => 'بەندەکان',
        'totals' => 'کۆی گشتی',
        'details' => 'وردەکارییەکان',
        'notes' => 'تێبینییەکان',
    ],
    'lines' => [
        'product' => 'بەرهەم',
        'description' => 'وەسف',
        'quantity' => 'بڕ',
        'unit' => 'یەکە',
        'unit_price' => 'نرخی یەکە',
        'tax' => 'باج',
    ],
    'actions' => [
        'record_bid' => 'تۆمارکردنی نرخ',
        'send_to_vendor' => 'ناردن بۆ فرۆشیار',
        'convert_to_order' => 'گۆڕین بۆ داواکاری',
    ],
    'notifications' => [
        'po_created_success' => 'داواکاری کڕین بە سەرکەوتوویی دروستکرا',
    ],
];
