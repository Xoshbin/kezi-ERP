<?php

return [
    'letter_of_credit' => 'اعتماد مستندي',
    'lc_number' => 'رقم الاعتماد',
    'bank_reference' => 'مرجع البنك',
    'vendor' => 'المستفيد (المورد)',
    'issuing_bank' => 'البنك المصدر',
    'amount' => 'مبلغ الاعتماد',
    'utilized_amount' => 'المبلغ المستخدم',
    'balance' => 'الرصيد',
    'issue_date' => 'تاريخ الإصدار',
    'expiry_date' => 'تاريخ الانتهاء',
    'shipment_date' => 'آخر تاريخ للشحن',
    'incoterm' => 'شروط التسليم (Incoterm)',
    'terms_and_conditions' => 'الشروط والأحكام',
    'notes' => 'ملاحظات',
    'purchase_order' => 'أمر الشراء',
    'type' => 'نوع الاعتماد',
    'status' => 'حالة الاعتماد',

    'statuses' => [
        'draft' => 'مسودة',
        'issued' => 'صادر',
        'negotiated' => 'متفاوض عليه',
        'partially_utilized' => 'مستخدم جزئياً',
        'fully_utilized' => 'مستخدم بالكامل',
        'expired' => 'منتهي الصلاحية',
        'cancelled' => 'ملغي',
    ],

    'types' => [
        'import' => 'استيراد',
        'export' => 'تصدير',
        'standby' => 'اعتماد ضامن',
    ],

    'charges' => [
        'lc_charge' => 'مصاريف الاعتماد',
        'charge_date' => 'تاريخ المصاريف',
        'description' => 'الوصف',
        'journal' => 'دفتر اليومية',
        'debit_account' => 'حساب المدين',
        'credit_account' => 'حساب الدائن',
    ],

    'utilizations' => [
        'title' => 'استخدامات الاعتماد',
        'vendor_bill' => 'فاتورة المورد',
        'utilization_date' => 'تاريخ الاستخدام',
    ],
];
