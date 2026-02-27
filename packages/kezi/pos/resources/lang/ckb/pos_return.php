<?php

return [
    'label' => 'گەڕاندنەوەی کاشێر',
    'plural_label' => 'گەڕاندنەوەکانی کاشێر',

    'status' => [
        'label' => 'بارودۆخ',
        'draft' => 'ڕەشنووس',
        'pending_approval' => 'چاوەڕوانی پەسەندکردن',
        'approved' => 'پەسەندکراو',
        'processing' => 'لە پرۆسەدایە',
        'completed' => 'تەواوکراو',
        'rejected' => 'ڕەتکراوە',
        'cancelled' => 'هەڵوەشێنراوە',
    ],

    'return_number' => 'ژمارەی گەڕاندنەوە',
    'original_order' => 'داواکاری ئەسڵی',
    'return_date' => 'بەرواری گەڕاندنەوە',
    'return_reason' => 'هۆکاری گەڕاندنەوە',
    'return_notes' => 'تێبینییەکان',
    'refund_amount' => 'بڕی گەڕاندنەوەی پارە',
    'restocking_fee' => 'کرێی گەڕاندن بۆ کۆگا',
    'refund_method' => 'شێوازی گەڕاندنەوەی پارە',
    'currency' => 'دراو',
    'requested_by' => 'داواکراوە لەلایەن',
    'approved_by' => 'پەسەندکراوە لەلایەن',
    'approved_at' => 'کاتی پەسەندکردن',
    'session' => 'خوول',

    'product' => 'کاڵا',
    'quantity_returned' => 'بڕی گەڕێندراوەتەوە',
    'unit_price' => 'نرخی تاک',
    'line_refund_amount' => 'گەڕاندنەوەی هێڵ',
    'item_condition' => 'بارودۆخی کاڵا',
    'restock' => 'گەڕاندن بۆ کۆگا',
    'restock_yes' => 'بەڵێ',
    'restock_no' => 'نەخێر',

    'credit_note' => 'نوسراوی کرێدیت',
    'credit_note_status' => 'بارودۆخی نوسراوی کرێدیت',
    'payment_reversal' => 'گەڕاندنەوەی پارەدان',
    'payment_reversal_status' => 'بارودۆخی گەڕاندنەوەی پارەدان',
    'stock_move' => 'جووڵەی کۆگا',

    'section' => [
        'details' => 'وردەکارییەکانی گەڕاندنەوە',
        'financials' => 'دارایی',
        'people' => 'کەسەکان',
        'lines' => 'هێڵەکانی گەڕاندنەوە',
        'accounting' => 'یەکخستن لەگەڵ ژمێریاری',
    ],

    'action' => [
        'approve' => 'پەسەندکردنی گەڕاندنەوە',
        'reject' => 'ڕەتکردنەوەی گەڕاندنەوە',
        'reject_reason' => 'هۆکاری ڕەتکردنەوە',
        'process' => 'کارپێکردنی گەڕاندنەوە',
    ],

    'notification' => [
        'approved' => 'گەڕاندنەوە بە سەرکەوتووی پەسەندکرا.',
        'rejected' => 'گەڕاندنەوە ڕەتکرایەوە.',
        'processed' => 'گەڕاندنەوە بە سەرکەوتووی کارپێکرا.',
        'process_failed' => 'شکستی هێنا لە کارپێکردنی گەڕاندنەوە.',
    ],

    'pending_approvals' => 'گەڕاندنەوە چاوەڕوانییەکان',
    'refunds_today' => 'کۆی گەڕانەوەی پارە (ئەمڕۆ)',
    'return_rate' => 'ڕێژەی گەڕاندنەوە',

    'pin' => [
        'invalid' => 'کۆدی مەنیجەر هەڵەیە. تکایە دووبارە هەوڵبدە.',
        'locked_out' => 'هەوڵی هەڵەی زۆر دراوە. تکایە دوای :seconds چرکە دووبارە هەوڵبدەرەوە.',
        'not_approvable' => 'ئەم گەڕاندنەوەیە لە بارودۆخی ئێستادا نابێت پەسەندبکرێت.',
    ],
];
