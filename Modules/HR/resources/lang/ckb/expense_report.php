<?php

return [
    'navigation' => [
        'name' => 'راپۆرتەکانی خەرجی',
        'label' => 'راپۆرتی خەرجی',
        'plural' => 'راپۆرتەکانی خەرجی',
        'group' => 'سەرچاوە مرۆییەکان',
    ],
    'fields' => [
        'report_number' => 'ژمارەی راپۆرت',
        'employee' => 'فەرمانبەر',
        'cash_advance' => 'پێشینەی پەیوەندیدار',
        'report_date' => 'بەرواری راپۆرت',
        'status' => 'دۆخ',
        'total_amount' => 'کۆی گشتی',
        'notes' => 'تێبینییەکان',
        'lines' => 'بەندەکانی خەرجی',
        'company' => 'کۆمپانیا',
    ],
    'lines' => [
        'expense_account' => 'هەژماری خەرجی',
        'description' => 'وەسف',
        'amount' => 'بڕ',
        'date' => 'بەروار',
        'receipt' => 'ژمارەی پسوڵە',
        'partner' => 'فرۆشیار',
    ],
    'actions' => [
        'submit' => 'ناردنی راپۆرت',
        'approve' => 'پەسەندکردنی راپۆرت',
        'cash_advance' => 'پێشینە',
    ],
    'status' => [
        'draft' => 'ڕەشنووس',
        'submitted' => 'نێردراو',
        'approved' => 'پەسەندکراو',
        'rejected' => 'ڕەتکراوە',
    ],
    'notifications' => [
        'submitted' => 'راپۆرتی خەرجی بە سەرکەوتوویی نێردرا.',
        'approved' => 'راپۆرت پەسەندکرا.',
        'created' => 'راپۆرت بە سەرکەوتوویی دروستکرا.',
    ],
];
