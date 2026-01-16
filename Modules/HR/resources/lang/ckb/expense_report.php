<?php

return [
    'navigation' => [
        'name' => 'ڕاپۆرتەکانی خەرجی',
        'label' => 'ڕاپۆرتی خەرجی',
        'plural' => 'ڕاپۆرتەکانی خەرجی',
        'group' => 'بەڕێوەبردنی سەرچاوە مرۆییەکان',
    ],
    'fields' => [
        'report_number' => 'ژمارەی ڕاپۆرت',
        'employee' => 'کارمەند',
        'cash_advance' => 'پێشینەی کاش',
        'report_date' => 'بەرواری ڕاپۆرت',
        'status' => 'دۆخ',
        'total_amount' => 'کۆی گشتی',
        'notes' => 'تێبینیەکان',
        'lines' => 'بەندەکانی خەرجی',
        'company' => 'کۆمپانیا',
        'created_at' => 'بەرواری دروستکردن',
    ],
    'lines' => [
        'expense_account' => 'هەژماری خەرجی',
        'description' => 'وەسف',
        'amount' => 'بڕ',
        'date' => 'بەروار',
        'receipt' => 'سەرچاوەی پسوڵە',
        'partner' => 'فرۆشیار',
    ],
    'actions' => [
        'submit' => 'ناردنی ڕاپۆرت',
        'approve' => 'پەسەندکردنی ڕاپۆرت',
        'cash_advance' => 'پێشینەی کاش',
    ],
    'status' => [
        'draft' => 'ڕەشنووس',
        'submitted' => 'نێردراو',
        'approved' => 'پەسەندکراو',
        'rejected' => 'ڕەتکراوە',
    ],
    'notifications' => [
        'submitted' => 'ڕاپۆرتی خەرجی بە سەرکەوتوویی نێردرا.',
        'approved' => 'ڕاپۆرتی خەرجی پەسەندکرا.',
        'created' => 'ڕاپۆرتی خەرجی بە سەرکەوتوویی دروستکرا.',
    ],
    'sections' => [
        'report_details' => 'وردەکاری ڕاپۆرت',
        'expense_lines' => 'هێڵەکانی خەرجی',
    ],
];
