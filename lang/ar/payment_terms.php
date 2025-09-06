<?php

return [
    'immediate_payment' => 'دفع فوري',
    'net_days' => 'صافي :days يوم',
    'installment_description' => ':percentage% خلال :days يوم',
    'immediate' => 'فوري',
    'end_of_month' => 'نهاية الشهر',
    'end_of_month_plus_days' => 'نهاية الشهر + :days يوم',
    'day_of_month' => ':day من الشهر + :days يوم',
    'with_discount' => '(خصم :percentage% إذا تم الدفع خلال :days يوم)',

    'types' => [
        'net' => 'أيام صافية',
        'end_of_month' => 'نهاية الشهر',
        'day_of_month' => 'يوم من الشهر',
        'immediate' => 'فوري',
        'net_description' => 'الدفع مستحق بعد عدد محدد من الأيام من تاريخ المستند',
        'end_of_month_description' => 'الدفع مستحق في نهاية الشهر بالإضافة إلى أيام إضافية',
        'day_of_month_description' => 'الدفع مستحق في يوم محدد من الشهر',
        'immediate_description' => 'الدفع مستحق فور الاستلام',
    ],

    'common' => [
        'immediate' => 'دفع فوري',
        'net_15' => 'صافي 15',
        'net_30' => 'صافي 30',
        'net_60' => 'صافي 60',
        'eom' => 'نهاية الشهر',
        'eom_plus_30' => 'نهاية الشهر + 30',
    ],

    'fields' => [
        'name' => 'اسم شروط الدفع',
        'description' => 'الوصف',
        'is_active' => 'نشط',
        'lines' => 'خطوط شروط الدفع',
        'sequence' => 'التسلسل',
        'type' => 'النوع',
        'days' => 'الأيام',
        'percentage' => 'النسبة المئوية',
        'day_of_month' => 'يوم من الشهر',
        'discount_percentage' => 'نسبة الخصم %',
        'discount_days' => 'أيام الخصم',
    ],

    'actions' => [
        'create' => 'إنشاء شروط دفع',
        'edit' => 'تعديل شروط الدفع',
        'delete' => 'حذف شروط الدفع',
        'add_line' => 'إضافة خط',
        'remove_line' => 'إزالة خط',
    ],

    'messages' => [
        'created' => 'تم إنشاء شروط الدفع بنجاح.',
        'updated' => 'تم تحديث شروط الدفع بنجاح.',
        'deleted' => 'تم حذف شروط الدفع بنجاح.',
        'cannot_delete_in_use' => 'لا يمكن حذف شروط الدفع المستخدمة.',
    ],

    'validation' => [
        'name_required' => 'اسم شروط الدفع مطلوب.',
        'percentage_sum' => 'يجب أن يساوي إجمالي النسبة المئوية 100%.',
        'percentage_positive' => 'يجب أن تكون النسبة المئوية موجبة.',
        'days_required' => 'الأيام مطلوبة لهذا النوع.',
        'day_of_month_required' => 'يوم من الشهر مطلوب لهذا النوع.',
        'day_of_month_range' => 'يجب أن يكون يوم الشهر بين 1 و 31.',
    ],
];
