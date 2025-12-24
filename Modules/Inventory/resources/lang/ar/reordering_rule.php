<?php

return [
    'label' => 'قاعدة إعادة الطلب',
    'plural_label' => 'قواعد إعادة الطلب',
    'create' => 'إضافة قاعدة إعادة طلب',

    'sections' => [
        'basic_info' => 'المعلومات الأساسية',
        'quantities' => 'الكميات',
        'timing' => 'التوقيت',
    ],

    'fields' => [
        'product' => 'المنتج',
        'location' => 'الموقع',
        'route' => 'المسار',
        'min_qty' => 'الحد الأدنى للكمية',
        'min_qty_help' => 'تفعيل إعادة الطلب عندما ينخفض المخزون دون هذا المستوى',
        'max_qty' => 'الحد الأقصى للكمية',
        'max_qty_help' => 'الكمية المستهدفة لإعادة الطلب',
        'safety_stock' => 'مخزون الأمان',
        'safety_stock_help' => 'مستوى المخزون الطارئ لإعادة الطلب العاجلة',
        'multiple' => 'المضاعف',
        'multiple_help' => 'يجب أن تكون كمية الطلب مضاعفاً لهذه القيمة',
        'lead_time_days' => 'وقت التسليم (أيام)',
        'lead_time_days_help' => 'وقت التسليم المتوقع بالأيام',
        'active' => 'نشط',
        'current_stock' => 'المخزون الحالي',
        'status' => 'الحالة',
        'updated_at' => 'آخر تحديث',
    ],

    'filters' => [
        'product' => 'المنتج',
        'location' => 'الموقع',
        'route' => 'المسار',
        'active' => 'نشط',
        'needs_reorder' => 'يحتاج إعادة طلب',
        'urgent' => 'عاجل',
    ],

    'status' => [
        'inactive' => 'غير نشط',
        'urgent' => 'عاجل',
        'reorder_needed' => 'يحتاج إعادة طلب',
        'ok' => 'جيد',
    ],

    'days' => 'أيام',
];
