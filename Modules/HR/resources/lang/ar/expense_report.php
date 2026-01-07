<?php

return [
    'navigation' => [
        'name' => 'تقارير المصروفات',
        'plural' => 'تقارير المصروفات',
        'group' => 'الموارد البشرية',
    ],
    'fields' => [
        'report_number' => 'رقم التقرير',
        'employee' => 'الموظف',
        'cash_advance' => 'السلفة المرتبطة',
        'report_date' => 'تاريخ التقرير',
        'status' => 'الحالة',
        'total_amount' => 'إجمالي المبلغ',
        'notes' => 'ملاحظات',
        'lines' => 'بنود المصروفات',
        'company' => 'الشركة',
    ],
    'lines' => [
        'expense_account' => 'حساب المصروف',
        'description' => 'الوصف',
        'amount' => 'المبلغ',
        'date' => 'التاريخ',
        'receipt' => 'رقم الإيصال',
        'partner' => 'المورد',
    ],
    'actions' => [
        'submit' => 'إرسال التقرير',
        'approve' => 'موافقة على التقرير',
    ],
    'status' => [
        'draft' => 'مسودة',
        'submitted' => 'مرسل',
        'approved' => 'موافق عليه',
        'rejected' => 'مرفوض',
    ],
    'notifications' => [
        'submitted' => 'تم إرسال تقرير المصروفات بنجاح.',
        'approved' => 'تمت الموافقة على التقرير.',
        'created' => 'تم إنشاء التقرير بنجاح.',
    ],
];
