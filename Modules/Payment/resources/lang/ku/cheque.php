<?php

return [
    'navigation_label' => 'چيکەکان',
    'plural_label' => 'چيکەکان',
    'singular_label' => 'چيک',

    'actions' => [
        'hand_over' => 'ڕادەستکردن',
        'deposit' => 'سپاردن',
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
            'deposited' => 'سپێردراو',
            'cleared' => 'پاککراوە',
            'bounced' => 'گەڕاوە',
            'cancelled' => 'هەڵوەشاوە',
            'voided' => 'پوچەڵ',
        ],
        'cheque_type' => [
            'payable' => 'پارەدان',
            'receivable' => 'وەرگرتن',
        ],
    ],

    'fields' => [
        'cheque_number' => 'ژمارەی چيک',
        'amount' => 'بڕ',
        'issue_date' => 'بەرواری دەرچوون',
        'due_date' => 'بەرواری شایستەبوون',
        'memo' => 'تیتینی',
        'payee' => 'وەرگر',
        'drawer' => 'کێشەر',
        'bank_name' => 'ناوی بانک',
    ],

    'widgets' => [
        'upcoming_cheques' => 'چيکە نزیکەکان',
    ],
];
