<?php

return [
    // Labels
    'label' => 'تاريخ القفل',
    'plural_label' => 'تواريخ القفل',

    // Fields
    'company' => 'شركة',
    'type' => 'نوع',
    'lock_date' => 'تاريخ القفل',
    'description' => 'وصف',
    'is_active' => 'نشط',
    'created_at' => 'تاريخ الإنشاء',
    'updated_at' => 'تاريخ التحديث',

    // Types
    'types' => [
        'tax_return_date' => 'تاريخ قفل الإقرار الضريبي',
        'everything_date' => 'تاريخ قفل كل شيء',
        'hard_lock' => 'قفل صارم',
    ],

    // Messages
    'period_locked' => 'الفترة مقفلة',
    'period_locked_message' => 'لا يمكن إجراء تعديلات على هذه الفترة لأنها مقفلة.',
    'lock_date_warning' => 'تحذير: هذا الإجراء سيقفل الفترة ولن يمكن التراجع عنه.',

    // Actions
    'lock_period' => 'قفل الفترة',
    'unlock_period' => 'إلغاء قفل الفترة',
];
