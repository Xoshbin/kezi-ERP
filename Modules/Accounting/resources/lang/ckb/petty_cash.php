<?php

return [
    'navigation_label' => 'نە سریە',
    'replenishment' => [
        'label' => 'پڕکردنەوەی نەسریە',
        'plural_label' => 'پڕکردنەوەکانی نەسریە',
        'section_details' => 'وردەکاری پڕکردنەوە',
        'replenishment_number' => 'ژمارەی پڕکردنەوە',
    ],
    'replenishments' => 'پڕکردنەوەکانی نەسریە',

    'fund' => [
        'label' => 'سندوقی نەسریە',
        'plural_label' => 'سندوقەکانی نەسریە',
        'section_details' => 'وردەکاری سندوق',
    ],
    'funds' => 'سندوقەکانی نەسریە',

    'voucher' => [
        'label' => 'پسووڵەی نەسریە',
        'plural_label' => 'پسووڵەکانی نەسریە',
        'expense_details' => 'وردەکاری خەرجی',
        'voucher_number' => 'ژمارەی پسووڵە',
        'post_modal_heading' => 'ناردنی پسووڵەی نەسریە',
        'post_modal_description' => 'ئەمە قەیدێکی ڕۆژنامەیی دروست دەکات و باڵانسی سندوق نوێ دەکاتەوە.',
    ],
    'vouchers' => 'پسووڵەکانی نەسریە',

    'fields' => [
        'petty_cash_fund' => 'سندوقی نە سریە',
        'fund_name' => 'ناوی سندوق',
        'imprest_amount' => 'بڕی پێشینە',
        'current_balance' => 'باڵانسی ئێستا',
        'replenishment_date' => 'ڕێکەوتی پڕکردنەوە',
        'replenishment_number' => 'ژمارەی پڕکردنەوە',
        'amount' => 'بڕ',
        'payment_method' => 'ڕێگای پارەدان',
        'reference' => 'ژمارەی بەڵگە',
        'expense_date' => 'ڕێکەوتی خەرجی',
        'voucher_date' => 'ڕێکەوتی پسووڵە',
        'expense_category' => 'جۆری خەرجی',
        'vendor_payee' => 'فرۆشیار/وەرگر (ئیختیاری)',
        'description' => 'وەسف',
        'receipt_reference' => 'سەرچاوەی پسووڵە',
        'custodian' => 'سەرپەرشتیار',
        'voucher_number' => 'ژمارەی پسووڵە',
    ],
    'actions' => [
        'post' => 'ناردن',
        'post_voucher' => 'ناردنی پسووڵە',
        'close_fund' => 'داخستنی سندوق',
        'replenish_fund' => 'پڕکردنەوەی سندوق',
    ],
    'status' => [
        'active' => 'چالاک',
        'closed' => 'داخراو',
        'draft' => 'ڕەشنووس',
        'posted' => 'نێردراو',
    ],
    'payment_methods' => [
        'cash' => 'کاش',
        'bank_transfer' => 'حەواڵەی بانکی',
        'cheque' => 'چەک',
    ],
    'messages' => [
        'voucher_posted' => 'پسووڵە بە سەرکەوتوویی نێردرا',
        'fund_created' => 'سندوق بە سەرکەوتوویی دروستکرا',
        'low_balance_warning' => 'باڵانسی سندوق کەمە',
    ],
    'helpers' => [
        'replenishment_amount' => 'بڕەکە بە شێوەی خۆکارانە حیساب دەکرێت لەسەر بنەمای باڵانسی سندوق',
        'replenishment_reference' => 'ژمارەی سەرچاوەی حەواڵەی بانکی یان چەک',
        'expense_category' => 'جۆری خەرجی هەڵبژێرە',
        'expense_description' => 'مەبەستی ئەم خەرجییە ڕوون بکەرەوە',
        'receipt_reference' => 'ژمارەی پسووڵەی دەرەکی',
    ],
];
