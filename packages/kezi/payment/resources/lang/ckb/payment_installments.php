<?php

return [
    'status' => [
        'pending' => 'چاوەڕوانکراو',
        'partially_paid' => 'بەشێک پارەدراو',
        'paid' => 'پارەدراو',
        'cancelled' => 'هەڵوەشاوە',
        'pending_description' => 'پارە هێشتا وەرنەگیراوە',
        'partially_paid_description' => 'بەشێک لە پارە وەرگیراوە',
        'paid_description' => 'بە تەواوی پارەدراوە',
        'cancelled_description' => 'قیست هەڵوەشاوەتەوە',
    ],

    'overdue_by_days' => ':days ڕۆژ دواکەوتووە',
    'paid' => 'پارەدراو',
    'due_today' => 'واجبە ئەمڕۆ',
    'due_in_days' => 'واجبە دوای :days ڕۆژ',

    'fields' => [
        'sequence' => 'قیستی #',
        'due_date' => 'ڕێکەوتی شایستە',
        'amount' => 'بڕ',
        'paid_amount' => 'بڕی پارەدراو',
        'remaining_amount' => 'ماوە',
        'status' => 'دۆخ',
        'discount_percentage' => 'داشکاندنی پارەدانی پێشوەختە',
        'discount_deadline' => 'وادەی داشکاندن',
    ],

    'actions' => [
        'apply_payment' => 'جێبەجێکردنی پارەدان',
        'view_payments' => 'بینینی پارەدانەکان',
        'send_reminder' => 'ناردنی بیرخەرەوە',
    ],

    'messages' => [
        'payment_applied' => 'پارەدان بە سەرکەوتوویی جێبەجێکرا.',
        'reminder_sent' => 'بیرخەرەوەی پارەدان نێردرا.',
        'early_discount_available' => 'داشکاندنی پارەدانی پێشوەختە بەردەستە تا :date',
    ],
];
