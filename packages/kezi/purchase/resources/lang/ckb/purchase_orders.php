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
        'vendor_currency_info' => 'زانیارییەکانی فرۆشیار و دراو',
        'vendor_currency_info_description' => 'وردەکارییەکانی فرۆشیار و دراو هەڵبژێرە بۆ ئەم داواکارییە',
        'order_details' => 'وردەکارییەکانی داواکاری',
        'order_details_description' => 'زانیاری بنەڕەتی دەربارەی داواکاری کڕین',
        'basic_information' => 'زانیاری بنەڕەتی',
        'vendor_currency_information' => 'زانیارییەکانی فرۆشیار و دراو',
        'delivery_info' => 'زانیاری گەیاندن',
        'delivery_information' => 'زانیاری گەیاندن',
        'line_items' => 'بەندەکان',
        'line_items_description' => 'بەرهەم و خزمەتگوزاری زیاد بکە بۆ ئەم داواکارییە',
        'notes' => 'تێبینی و مەرجەکان',
        'totals' => 'کۆی گشتی',
        'lines' => 'بڕگەکانی داواکاری',
        'attachments' => 'پاشکۆکان',
        'attachments_description' => 'بەڕێوەبردنی پاشکۆکانی بەڵگەنامە بۆ ئەم داواکارییە',
    ],

    'fields' => [
        'document_currency' => 'دراوی بەڵگەنامە',
        'company_currency' => 'دراوی کۆمپانیا',
        'id' => 'ناسنامە',
        'po_number' => 'ژمارەی داواکاری',
        'status' => 'دۆخ',
        'po_date' => 'بەرواری داواکاری',
        'reference' => 'سەرچاوە',
        'vendor' => 'فرۆشیار',
        'currency' => 'دراو',
        'expected_delivery_date' => 'بەرواری گەیشتنی پێشبینیکراو',
        'incoterm' => 'ئین کۆتێرم',
        'incoterm_location' => 'شوێنی ئینکۆتێرم',
        'delivery_location' => 'شوێنی گەیاندن',
        'notes' => 'تێبینیەکان',
        'terms_and_conditions' => 'مەرج و ڕێنماییەکان',
        'exchange_rate' => 'نرخی ئاڵوگۆڕ',
        'total_amount' => 'کۆی گشتی',
        'total_tax' => 'کۆی باج',
        'created_by' => 'دروستکراوە لەلایەن',
        'created_at' => 'بەرواری دروستکردن',
        'updated_at' => 'کاتی نوێکردنەوە',
        'created_by_user' => 'دروستکراوە لەلایەن',
        'confirmed_at' => 'کاتی پەسەندکردن',
        'cancelled_at' => 'کاتی هەڵوەشاندنەوە',
        'exchange_rate_at_creation' => 'نرخی ئاڵوگۆڕ',
        'total_amount_company_currency' => 'کۆی گشتی (دراوی کۆمپانیا)',
        'total_tax_company_currency' => 'باج (دراوی کۆمپانیا)',
        'billing_status' => 'دۆخی پسوڵە',
        // Line item fields
        'lines' => 'بەندەکان',
        'product' => 'بەرهەم',
        'description' => 'وەسف',
        'quantity' => 'بڕ',
        'unit_price' => 'نرخی یەکە',
        'tax' => 'باج',
        'shipping_type' => 'جۆری ناردن',
    ],

    'line_fields' => [
        'product' => 'بەرهەم',
        'description' => 'پێناسە',
        'quantity' => 'بڕ',
        'quantity_received' => 'بڕی وەرگیراو',
        'remaining_quantity' => 'بڕی ماوە',
        'unit_price' => 'نرخی یەکە',
        'subtotal' => 'کۆی لاوەکی',
        'tax' => 'باج',
        'total_line_tax' => 'باجی هێڵ',
        'total' => 'کۆی گشتی',
        'expected_delivery_date' => 'گەیاندنی پێشبینیکراو',
        'notes' => 'تێبینیەکان',
    ],

    'status' => [
        // Pre-commitment phase
        'rfq' => 'داواکاری بۆ نرخ',
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

    'actions' => [
        'create' => 'دروستکردنی داواکاری کڕین',
        'edit' => 'دەستکاریکردنی داواکاری کڕین',
        'view' => 'بینینی داواکاری کڕین',
        'send_rfq' => 'ناردنی داواکاری نرخ',
        'send' => 'ناردن بۆ فرۆشیار',
        'confirm' => 'پەسەندکردن',
        'ready_to_receive' => 'ئامادەیە بۆ وەرگرتن',
        'ready_to_receive_confirmation_title' => 'ئامادەیە بۆ وەرگرتنی کاڵاکان',
        'ready_to_receive_confirmation_description' => 'ئەمە داواکاری کڕینەکە وەک ئامادە بۆ وەرگرتنی کاڵاکان لە فرۆشیار دەستنیشان دەکات.',
        'mark_done' => 'وەک تەواو دەستنیشانی بکە',
        'cancel' => 'هەڵوەشاندنەوە',
        'receive_goods' => 'وەرگرتنی کاڵاکان',
        'create_bill' => 'دروستکردنی پسوڵەی فرۆشیار',
        'create_bill_confirmation_title' => 'دروستکردنی پسوڵەی فرۆشیار',
        'create_bill_confirmation_description' => 'ئەمە بە شێوەیەکی خۆکار پسوڵەیەکی فرۆشیار دروست دەکات بە هەموو هێڵەکانی ئەم داواکارییەی کڕین. پسوڵەکە لە دۆخی ڕەشنووسدا دروست دەبێت بۆ پێداچوونەوەی تۆ.',
        'add_line' => 'زیادکردنی هێڵ',
        'remove_line' => 'سڕینەوەی هێڵ',
    ],

    'messages' => [
        'created' => 'داواکاری کڕین بە سەرکەوتوویی دروستکرا.',
        'updated' => 'داواکاری کڕین بە سەرکەوتوویی نوێکرایەوە.',
        'confirmed' => 'داواکاری کڕین بە سەرکەوتوویی پەسەندکرا.',
        'cancelled' => 'داواکاری کڕین بە سەرکەوتوویی هەڵوەشێنرایەوە.',
        'cannot_edit_confirmed' => 'ناتوانرێت دەستکاری داواکاری کڕینی پەسەندکراو بکرێت.',
        'cannot_confirm_without_lines' => 'ناتوانرێت داواکاری کڕین بەبێ هیچ هێڵێک پەسەند بکرێت.',
        'cannot_cancel_completed' => 'ناتوانرێت داواکاری کڕینی تەواوبوو هەڵبوەشێنرێتەوە.',
        'fully_received' => 'هەموو بابەتەکان بۆ ئەم داواکارییەی کڕین وەرگیراون.',
        'partially_received' => 'هەندێک بابەت بۆ ئەم داواکارییەی کڕین وەرگیراون.',
    ],

    'notifications' => [
        'confirmed' => 'داواکاری کڕین بە سەرکەوتوویی پەسەندکرا',
        'confirm_failed' => 'پەسەندکردنی داواکاری کڕین سەرکەوتوو نەبوو',
        'cancelled' => 'داواکاری کڕین بە سەرکەوتوویی هەڵوەشێنرایەوە',
        'rfq_sent' => 'داواکاری نرخ بە سەرکەوتوویی بۆ فرۆشیار نێردرا.',
        'sent' => 'داواکاری کڕین بە سەرکەوتوویی بۆ فرۆشیار نێردرا.',
        'ready_to_receive' => 'داواکاری کڕین ئێستا ئامادەیە بۆ وەرگرتنی کاڵاکان.',
        'marked_done' => 'داواکاری کڕین بە سەرکەوتوویی وەک تەواو دەستنیشانکرا.',
        'bill_created_successfully' => 'پسوڵەی فرۆشیار بە سەرکەوتوویی دروستکرا',
        'bill_created_body' => 'پسوڵەی فرۆشیار :reference دروستکراوە و ئامادەیە بۆ پێداچوونەوە.',
        'bill_creation_failed' => 'دروستکردنی پسوڵەی فرۆشیار سەرکەوتوو نەبوو',
        'update_not_allowed' => 'نوێکردنەوە ڕێگەپێنەدراوە',
    ],

    'help' => [
        'po_number' => 'بە شێوەی خۆکار دروست دەکرێت',
        'reference' => 'سەرچاوەی ناوخۆیی یان ژمارەی ئۆفەری فرۆشیار',
        'terms_and_conditions' => 'مەرجی ستاندارد بۆ ئەم داواکارییە',
        'exchange_rate' => 'نرخی ئاڵوگۆڕی بەکارهاتوو بۆ گۆڕینی دراو کاتێک داواکارییەکە دروستکراوە.',
        'delivery_location' => 'شوێنی بنەڕەتی کە کاڵاکانی لێ وەردەگیرێت.',
        'status_can_create_bill' => 'دەتوانرێت پسوڵەی فرۆشیار دروست بکرێت لەم داواکارییە.',
        'status_cannot_create_bill' => 'دۆخەکە بگۆڕە بۆ پەسەندکراو یان وەرگرتن بۆ ئەوەی بتوانیت پسوڵە دروست بکەیت.',
        'status_bills_already_exist' => 'پسوڵەی فرۆشیار پێشتر دروستکراوە بۆ ئەم داواکارییە.',
        'status_forward_only' => 'دۆخەکە تەنها دەتوانرێت بۆ پێشەوە بگۆڕدرێت.',
    ],
];
