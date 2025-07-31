<?php

return [
    // Labels
    'label' => 'هاوبەش',
    'plural_label' => 'هاوبەشەکان',

    // Basic Information
    'company' => 'کۆمپانیا',
    'name' => 'ناو',
    'type' => 'جۆر',
    'contact_person' => 'کەسی پەیوەندیدار',
    'email' => 'ئیمەیڵ',
    'phone' => 'تەلەفۆن',
    'tax_id' => 'ژمارەی باج',
    'is_active' => 'چالاکە',

    // Address
    'address_line_1' => 'ناونیشانی یەکەم',
    'address_line_2' => 'ناونیشانی دووەم',
    'city' => 'شار',
    'state' => 'پارێزگا',
    'zip_code' => 'کۆدی پۆستە',
    'country' => 'وڵات',

    // Timestamps
    'created_at' => 'کاتی دروستکردن',
    'updated_at' => 'کاتی نوێکردنەوە',
    'deleted_at' => 'کاتی سڕینەوە',

    // Relation Managers
    'invoices_relation_manager' => [
        'title' => 'پسوڵەکان',
        'invoice_number' => 'ژمارەی پسوڵە',
        'invoice_date' => 'بەرواری پسوڵە',
        'due_date' => 'بەرواری شایستە',
        'status' => 'بارودۆخ',
        'total_amount' => 'کۆی گشتی',
    ],
    'vendor_bills_relation_manager' => [
        'title' => 'پسوڵەکانی فرۆشیار',
        'bill_reference' => 'سەرچاوەی پسوڵە',
        'bill_date' => 'بەرواری پسوڵە',
        'accounting_date' => 'بەرواری ژمێریاری',
        'due_date' => 'بەرواری شایستە',
        'status' => 'بارودۆخ',
        'total_amount' => 'کۆی گشتی',
    ],
    'payments_relation_manager' => [
        'title' => 'پارەدانەکان',
        'payment_date' => 'بەرواری پارەدان',
        'amount' => 'بڕ',
        'payment_type' => 'جۆری پارەدان',
        'reference' => 'سەرچاوە',
        'status' => 'بارودۆخ',
    ],
];