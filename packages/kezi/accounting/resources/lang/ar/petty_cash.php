<?php

return [
    'fund' => 'صندوق النثرية',
    'funds' => 'صناديق النثرية',
    'voucher' => 'قسيمة نثرية',
    'vouchers' => 'قسائم نثرية',
    'replenishment' => 'تعويض النثرية',
    'replenishments' => 'تعويضات النثرية',

    // Fund fields
    'fund_name' => 'اسم الصندوق',
    'custodian' => 'الأمين',
    'imprest_amount' => 'المبلغ الثابت',
    'current_balance' => 'الرصيد الحالي',

    // Voucher fields
    'voucher_number' => 'رقم القسيمة',
    'voucher_date' => 'تاريخ القسيمة',
    'expense_category' => 'فئة المصروف',
    'receipt_reference' => 'مرجع الإيصال',

    // Replenishment fields
    'replenishment_number' => 'رقم التعويض',
    'replenishment_date' => 'تاريخ التعويض',
    'payment_method' => 'طريقة الدفع',

    // Statuses
    'status' => [
        'active' => 'نشط',
        'closed' => 'مغلق',
        'draft' => 'مسودة',
        'posted' => 'منشور',
    ],

    // Payment methods
    'payment_methods' => [
        'cash' => 'نقدي',
        'bank_transfer' => 'تحويل بنكي',
        'cheque' => 'شيك',
    ],

    // Actions
    'post_voucher' => 'نشر القسيمة',
    'close_fund' => 'إغلاق الصندوق',
    'replenish_fund' => 'تعويض الصندوق',

    // Messages
    'voucher_posted' => 'تم نشر القسيمة بنجاح',
    'fund_created' => 'تم إنشاء الصندوق بنجاح',
    'low_balance_warning' => 'رصيد الصندوق منخفض',
];
