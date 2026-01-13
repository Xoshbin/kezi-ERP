<?php

return [
    'navigation_label' => 'چەکەکان',
    'plural_label' => 'چەکەکان',
    'singular_label' => 'چەک',

    'actions' => [
        'hand_over' => 'ڕادەستکردن',
        'deposit' => 'خستنە سەر هەژمار',
        'clear' => 'پاککردنەوە',
        'bounce' => 'گەڕانەوە',
        'cancel' => 'هەڵوەشاندنەوە',
        'print' => 'چاپکردن',
    ],

    'enums' => [
        'cheque_status' => [
            'draft' => 'ڕەشنووس',
            'printed' => 'چاپکراو',
            'handed_over' => 'ڕادەستکراو',
            'deposited' => 'لە بانک دانراو',
            'cleared' => 'پارە وەرگیراو',
            'bounced' => 'گەڕاوە',
            'cancelled' => 'هەڵوەشاوە',
            'voided' => 'پوچەڵکراو',
        ],
        'cheque_type' => [
            'payable' => 'بۆ پارەدان',
            'receivable' => 'بۆ وەرگرتن',
        ],
    ],

    'fields' => [
        'cheque_number' => 'ژمارەی چەک',
        'amount' => 'بڕ',
        'issue_date' => 'ڕێکەوتی دەرچوون',
        'due_date' => 'ڕێکەوتی شایستە',
        'memo' => 'تێبینی',
        'payee' => 'وەرگر',
        'drawer' => 'دەرکەر',
        'bank_name' => 'ناوی بانک',
    ],

    'widgets' => [
        'upcoming_cheques' => 'چەکەکانی داهاتوو',
    ],
];
