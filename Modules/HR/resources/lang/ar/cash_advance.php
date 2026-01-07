<?php

return [
    'navigation' => [
        'name' => 'السلف النقدية',
        'plural' => 'السلف النقدية',
        'group' => 'الموارد البشرية',
    ],
    'fields' => [
        'employee' => 'الموظف',
        'advance_number' => 'رقم السلفة',
        'amount' => 'المبلغ المطلوب',
        'currency' => 'العملة',
        'request_date' => 'تاريخ الطلب',
        'status' => 'الحالة',
        'purpose' => 'الغرض',
        'repayment_terms' => 'شروط السداد',
        'notes' => 'ملاحظات',
        'approved_amount' => 'المبلغ الموافق عليه',
        'approved_at' => 'تاريخ الموافقة',
        'disbursed_at' => 'تاريخ الصرف',
        'settled_at' => 'تاريخ التسوية',
    ],
    'actions' => [
        'submit' => 'إرسال للموافقة',
        'approve' => 'موافقة',
        'reject' => 'رفض',
        'disburse' => 'صرف الأموال',
        'create_expense_report' => 'إنشاء تقرير مصروفات',
        'settle' => 'تسوية السلفة',
    ],
    'status' => [
        'draft' => 'مسودة',
        'pending_approval' => 'بانتظار الموافقة',
        'approved' => 'موافق عليه',
        'disbursed' => 'تم الصرف',
        'pending_settlement' => 'بانتظار التسوية',
        'settled' => 'تمت التسوية',
        'rejected' => 'مرفوض',
        'cancelled' => 'ملغى',
    ],
    'notifications' => [
        'submitted' => 'تم إرسال السلفة للموافقة.',
        'approved' => 'تمت الموافقة على السلفة بنجاح.',
        'rejected' => 'تم رفض السلفة.',
        'disbursed' => 'تم صرف الأموال بنجاح.',
        'settled' => 'تمت تسوية السلفة بنجاح.',
    ],
];
