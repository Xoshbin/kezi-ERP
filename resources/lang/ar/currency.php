<?php

return [
    // Labels
    'label' => 'عملة',
    'plural_label' => 'عملات',

    // Fields
    'code' => 'رمز',
    'name' => 'اسم',
    'symbol' => 'رمز',
    'exchange_rate' => 'سعر الصرف',
    'is_active' => 'نشط',
    'last_updated_at' => 'آخر تحديث في',
    'created_at' => 'تاريخ الإنشاء',
    'updated_at' => 'تاريخ التحديث',

    // Exchange Rates
    'exchange_rates' => [
        'label' => 'سعر الصرف',
        'plural_label' => 'أسعار الصرف',
        'currency' => 'عملة',
        'rate' => 'سعر الصرف',
        'effective_date' => 'تاريخ السريان',
        'source' => 'مصدر',
        'rate_helper' => 'السعر نسبة إلى عملة الشركة الأساسية (1 عملة أجنبية = X عملة أساسية)',
        'recent_filter' => 'حديث (آخر 30 يوم)',
        'sources' => [
            'manual' => 'إدخال يدوي',
            'api' => 'استيراد API',
            'bank' => 'سعر البنك',
            'central_bank' => 'البنك المركزي',
            'seeder' => 'بذرة قاعدة البيانات',
        ],
    ],
];
