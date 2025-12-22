<?php

return [
    // Labels
    'label' => 'حساب',
    'plural_label' => 'حسابات',

    // Basic Information
    'company' => 'شركة',
    'code' => 'رمز',
    'code_help' => 'يتم إنشاؤه تلقائيًا من اختيار المجموعة. يمكنك التعديل إذا لزم الأمر.',
    'name' => 'اسم',
    'type' => 'نوع',
    'is_deprecated' => 'مهجور',
    'group' => 'المجموعة',
    'group_help' => 'يتم تعيين الحسابات تلقائيًا بناءً على الرمز، ولكن يمكنك التجاوز يدويًا.',
    'allow_reconciliation' => 'السماح بالتسوية',
    'allow_reconciliation_help' => 'السماح باستخدام هذا الحساب في عمليات التسوية (الذمم المدينة، الذمم الدائنة، البنك).',
    'created_at' => 'تاريخ الإنشاء',
    'updated_at' => 'تاريخ التحديث',

    // Journal Entry Lines Relation Manager
    'journal_entry_lines' => [
        'label' => 'بند قيد اليومية',
        'plural_label' => 'بنود قيود اليومية',
        'journal_entry' => 'قيد اليومية',
        'debit' => 'مدين',
        'credit' => 'دائن',
        'description' => 'وصف',
    ],

    // Section
    'basic_information' => 'المعلومات الأساسية',
    'basic_information_description' => 'رمز الحساب، الاسم، النوع والخيارات.',
    'is_deprecated_help' => 'ضع علامة على هذا الحساب كمهجور إذا لم يعد مستخدمًا.',

    // Wizard Steps
    'wizard' => [
        'step_group' => 'مجموعة الحساب',
        'step_group_description' => 'اختر المجموعة التي ينتمي إليها هذا الحساب.',
        'step_details' => 'تفاصيل الحساب',
        'step_details_description' => 'أدخل رمز الحساب والاسم والنوع.',
        'step_options' => 'الخيارات',
        'step_options_description' => 'تكوين إعدادات الحساب الإضافية.',
    ],
];
