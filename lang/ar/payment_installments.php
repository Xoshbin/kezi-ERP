<?php

return [
    'status' => [
        'pending' => 'معلق',
        'partially_paid' => 'مدفوع جزئياً',
        'paid' => 'مدفوع',
        'cancelled' => 'ملغي',
        'pending_description' => 'لم يتم استلام الدفع بعد',
        'partially_paid_description' => 'تم استلام دفع جزئي',
        'paid_description' => 'مدفوع بالكامل',
        'cancelled_description' => 'تم إلغاء القسط',
    ],

    'overdue_by_days' => 'متأخر بـ :days يوم',
    'paid' => 'مدفوع',
    'due_today' => 'مستحق اليوم',
    'due_in_days' => 'مستحق خلال :days يوم',

    'fields' => [
        'sequence' => 'القسط رقم',
        'due_date' => 'تاريخ الاستحقاق',
        'amount' => 'المبلغ',
        'paid_amount' => 'المبلغ المدفوع',
        'remaining_amount' => 'المتبقي',
        'status' => 'الحالة',
        'discount_percentage' => 'خصم الدفع المبكر',
        'discount_deadline' => 'موعد انتهاء الخصم',
    ],

    'actions' => [
        'apply_payment' => 'تطبيق الدفع',
        'view_payments' => 'عرض المدفوعات',
        'send_reminder' => 'إرسال تذكير',
    ],

    'messages' => [
        'payment_applied' => 'تم تطبيق الدفع بنجاح.',
        'reminder_sent' => 'تم إرسال تذكير الدفع.',
        'early_discount_available' => 'خصم الدفع المبكر متاح حتى :date',
    ],
];
