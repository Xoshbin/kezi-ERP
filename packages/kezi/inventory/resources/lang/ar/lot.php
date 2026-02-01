<?php

return [
    'label' => 'دفعة',
    'plural_label' => 'دفعات',

    'sections' => [
        'basic_info' => 'المعلومات الأساسية',
        'expiration' => 'انتهاء الصلاحية',
    ],

    'fields' => [
        'lot_code' => 'رمز الدفعة',
        'product' => 'المنتج',
        'expiration_date' => 'تاريخ انتهاء الصلاحية',
        'expiration_date_help' => 'اتركه فارغاً إذا كان المنتج لا ينتهي',
        'days_until_expiration' => 'أيام حتى انتهاء الصلاحية',
        'active' => 'نشط',
        'stock_quants_count' => 'مواقع المخزون',
        'created_at' => 'تاريخ الإنشاء',
    ],

    'filters' => [
        'product' => 'المنتج',
        'active' => 'نشط',
        'expired' => 'منتهي الصلاحية',
        'expiring_soon' => 'ينتهي قريباً (30 يوماً)',
        'no_expiration' => 'بدون تاريخ انتهاء',
    ],

    'no_expiration' => 'بدون انتهاء صلاحية',
    'expires_in_days' => 'ينتهي خلال :days يوماً',
    'expires_today' => 'ينتهي اليوم',
    'expired_days_ago' => 'انتهى منذ :days يوماً',
];
