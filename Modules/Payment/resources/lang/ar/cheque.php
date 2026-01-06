<?php

return [
    'navigation_label' => 'الشيكات',
    'plural_label' => 'شيكات',
    'singular_label' => 'شيك',

    'actions' => [
        'hand_over' => 'تسليم',
        'deposit' => 'إيداع',
        'clear' => 'تحصيل',
        'bounce' => 'ارتجاع',
        'cancel' => 'إلغاء',
        'print' => 'طباعة',
    ],

    'enums' => [
        'cheque_status' => [
            'draft' => 'مسودة',
            'printed' => 'مطبوع',
            'handed_over' => 'تم التسليم',
            'deposited' => 'تم الإيداع',
            'cleared' => 'تم التحصيل',
            'bounced' => 'مرتجع',
            'cancelled' => 'ملغي',
            'voided' => 'باطل',
        ],
        'cheque_type' => [
            'payable' => 'دفع',
            'receivable' => 'قبض',
        ],
    ],

    'fields' => [
        'cheque_number' => 'رقم الشيك',
        'amount' => 'المبلغ',
        'issue_date' => 'تاريخ الإصدار',
        'due_date' => 'تاريخ الاستحقاق',
        'memo' => 'ملاحظات',
        'payee' => 'المستفيد',
        'drawer' => 'الساحب',
        'bank_name' => 'اسم البنك',
    ],

    'widgets' => [
        'upcoming_cheques' => 'شيكات مستحقة قريباً',
    ],
];
