<?php

return [
    'navigation' => [
        'name' => 'پێشینەی نەقد',
        'plural' => 'پێشینە نەقدییەکان',
        'group' => 'سەرچاوە مرۆییەکان',
    ],
    'fields' => [
        'employee' => 'فەرمانبەر',
        'advance_number' => 'ژمارەی پێشینە',
        'amount' => 'بڕی داواکراو',
        'currency' => 'دراو',
        'request_date' => 'بەرواری داواکاری',
        'status' => 'دۆخ',
        'purpose' => 'مەبەست',
        'repayment_terms' => 'مەرجەکانی دانەوە',
        'notes' => 'تێبینییەکان',
        'approved_amount' => 'بڕی پەسەندکراو',
        'approved_at' => 'بەرواری پەسەندکردن',
        'disbursed_at' => 'بەرواری خەرجکردن',
        'settled_at' => 'بەرواری پاکتاوکردن',
    ],
    'actions' => [
        'submit' => 'ناردن بۆ پەسەندکردن',
        'approve' => 'پەسەندکردن',
        'reject' => 'ڕەتکردنەوە',
        'disburse' => 'خەرجکردنی پارە',
        'create_expense_report' => 'دروستکردنی راپۆرتی خەرجی',
        'settle' => 'پاکتاوکردنی پێشینە',
    ],
    'status' => [
        'draft' => 'ڕەشنووس',
        'pending_approval' => 'لە چاوەڕوانی پەسەندکردن',
        'approved' => 'پەسەندکراو',
        'disbursed' => 'خەرجکراو',
        'pending_settlement' => 'لە چاوەڕوانی پاکتاوکردن',
        'settled' => 'پاکتاوکراو',
        'rejected' => 'ڕەتکراوە',
        'cancelled' => 'هەڵوەشاوە',
    ],
    'notifications' => [
        'submitted' => 'پێشینە نێردرا بۆ پەسەندکردن.',
        'approved' => 'پێشینە بە سەرکەوتوویی پەسەندکرا.',
        'rejected' => 'پێشینە ڕەتکرایەوە.',
        'disbursed' => 'پارە بە سەرکەوتوویی خەرجکرا.',
        'settled' => 'پێشینە بە سەرکەوتوویی پاکتاوکرا.',
    ],
];
