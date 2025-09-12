<?php

return [
    // Labels
    'label' => 'هاوبەش',
    'plural_label' => 'هاوبەشەکان',

    // Form Sections
    'basic_information' => 'زانیاری بنەڕەتی',
    'basic_information_description' => 'وردەکاری گرنگی هاوبەش و جۆری بازرگانی',
    'contact_information' => 'زانیاری پەیوەندی',
    'contact_information_description' => 'وردەکاری پەیوەندی بۆ ئەم هاوبەشە',
    'address_information' => 'زانیاری ناونیشان',
    'address_information_description' => 'شوێنی فیزیکی و ناونیشانی پۆستە',
    'accounting_configuration' => 'ڕێکخستنی ژمێریاری',
    'accounting_configuration_description' => 'دیاریکردنی هەژمار بۆ مامەڵە داراییەکان',

    // Basic Information
    'company' => 'کۆمپانیا',
    'name' => 'ناو',
    'type' => 'جۆر',
    'contact_person' => 'کەسی پەیوەندیدار',
    'email' => 'ئیمەیڵ',
    'phone' => 'تەلەفۆن',
    'tax_id' => 'ژمارەی باج',
    'is_active' => 'چالاکە',
    'receivable_account' => 'هەژماری وەرگرتن',
    'payable_account' => 'هەژماری پارەدان',
    'receivable_account_help' => 'هەژمار بۆ بەدواداچوونی ئەو پارەیەی ئەم هاوبەشە قەرزداری ئێمەیە (پسووڵەی کڕیار)',
    'payable_account_help' => 'هەژمار بۆ بەدواداچوونی ئەو پارەیەی ئێمە قەرزداری ئەم هاوبەشەین (پسووڵەی فرۆشیار)',
    'create_receivable_account' => 'دروستکردنی هەژماری وەرگرتن',
    'create_payable_account' => 'دروستکردنی هەژماری پارەدان',

    // Address
    'address' => 'ناونیشان',
    'address_line_1' => 'ناونیشانی یەکەم',
    'address_line_2' => 'ناونیشانی دووەم',
    'city' => 'شار',
    'state' => 'پارێزگا',
    'zip_code' => 'کۆدی پۆستە',
    'country' => 'وڵات',

    // Financial Information
    'customer_outstanding' => 'قەرزی کڕیار',
    'customer_overdue' => 'قەرزی دواکەوتوو - کڕیار',
    'vendor_outstanding' => 'قەرزی فرۆشیار',
    'vendor_overdue' => 'قەرزی دواکەوتوو - فرۆشیار',
    'last_activity' => 'دوایین چالاکی',
    'no_activity' => 'هیچ چالاکییەک نییە',
    'has_overdue_amounts' => 'قەرزی دواکەوتووی هەیە',
    'has_outstanding_balance' => 'قەرزی ماوەی هەیە',

    // Widget Labels
    'widgets' => [
        // Common
        'includes_overdue' => ':amount دواکەوتوو لەخۆدەگرێت',
        'days' => 'ڕۆژ',
        'immediate_attention' => 'پێویستی بە سەرنجی خێرایە',
        'payment_performance' => 'کارکردی پارەدان',
        'current_month_activity' => 'چالاکی مانگی ئێستا',
        'urgent_payments' => 'پارەدانی بەپەلە',
        'our_payment_performance' => 'کارکردی پارەدانمان',
        'last_month_payments' => 'پارەدانی مانگی ڕابردوو',

        // Customer Widgets
        'total_outstanding' => 'کۆی قەرزی ماوە',
        'due_within_7_days' => 'قەرزی ٧ ڕۆژی داهاتوو',
        'average_payment_time' => 'ناوەندی کاتی پارەدان',
        'received_this_month' => 'ئەم مانگە وەرگیراو',

        // Vendor Widgets
        'total_to_pay' => 'کۆی پێدراو',
        'paid_last_month' => 'مانگی ڕابردوو دراو',

        // Legacy (keeping for compatibility)
        'customer_owes_us' => 'کڕیار قەرزدارمانە',
        'overdue_amount' => 'بڕی دواکەوتوو',
        'no_overdue_amounts' => 'هیچ بڕێکی دواکەوتوو نییە',
        'requires_attention' => 'پێویستی بە سەرنج هەیە',
        'due_within_30_days' => 'لە ماوەی ٣٠ ڕۆژدا',
        'upcoming_collections' => 'کۆکردنەوەی داهاتوو',
        'avg_payment_days' => 'ناوەندی ڕۆژانی پارەدان',
        'no_payment_history' => 'مێژووی پارەدان نییە',
        'average_collection_time' => 'ناوەندی کاتی کۆکردنەوە',
        'last_payment' => 'دوایین پارەدان',
        'no_payments' => 'هیچ پارەدانێک نییە',
        'no_payment_received' => 'هیچ پارەیەک وەرنەگیراوە',
        'total_payable' => 'کۆی قەرزی پێدراو',
        'we_owe_vendor' => 'ئێمە قەرزدار فرۆشیارین',
        'overdue_payable' => 'قەرزی دواکەوتووی پێدراو',
        'no_overdue_payments' => 'هیچ پارەدانێکی دواکەوتوو نییە',
        'payment_required' => 'پارەدان پێویستە',
        'pay_within_7_days' => 'لە ماوەی ٧ ڕۆژدا بدە',
        'urgent_payments_needed' => 'پارەدانی بەپەلە پێویستە',
        'pay_within_30_days' => 'لە ماوەی ٣٠ ڕۆژدا بدە',
        'upcoming_payments' => 'پارەدانی داهاتوو',
        'our_avg_payment_days' => 'ناوەندی ڕۆژانی پارەدانمان',
        'our_payment_time' => 'کاتی پارەدانمان',
        'last_payment_made' => 'دوایین پارەدانی کراو',
        'no_payment_made' => 'هیچ پارەیەک نەدراوە',

        // Overview Widgets
        'lifetime_value' => 'نرخی تەواوی ژیان',
        'total_business_volume' => 'کۆی قەبارەی بازرگانی',
        'this_month' => 'ئەم مانگە',
        'no_activity_this_month' => 'ئەم مانگە چالاکی نییە',
        'current_month_volume' => 'قەبارەی مانگی ئێستا',
        'performance_score' => 'نمرەی کارکرد',
        'excellent_performance' => 'کارکردی نایاب',
        'good_performance' => 'کارکردی باش',
        'average_performance' => 'کارکردی ناوەند',
        'poor_performance' => 'کارکردی خراپ',
        'very_poor_performance' => 'کارکردی زۆر خراپ',
        'last_activity' => 'دوایین چالاکی',
        'today' => 'ئەمڕۆ',
        'days_ago' => ':days ڕۆژ لەمەوبەر',
        'no_activity' => 'هیچ چالاکییەک نییە',
        'no_transactions' => 'هیچ مامەڵەیەک تۆمار نەکراوە',
        'partner_type' => 'جۆری هاوبەش',
        'customer_only' => 'تەنها کڕیار',
        'vendor_only' => 'تەنها فرۆشیار',
        'customer_and_vendor' => 'کڕیار و فرۆشیار',
        'status' => 'دۆخ',
        'active' => 'چالاک',
        'inactive' => 'ناچالاک',
        'active_partner' => 'هاوبەشی چالاک',
        'inactive_partner' => 'هاوبەشی ناچالاک',
    ],

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
        'bill_reference' => 'ژمارەی بەڵگەی پسووڵە',
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
        'reference' => 'ژمارەی بەڵگە',
        'status' => 'بارودۆخ',
    ],
];
