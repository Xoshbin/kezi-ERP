<?php

return [
    // Labels
    'type_label' => 'نوع ضريبة الخصم من المنبع',
    'types_label' => 'أنواع ضريبة الخصم من المنبع',
    'entry_label' => 'قيد ضريبة الخصم من المنبع',
    'entries_label' => 'قيود ضريبة الخصم من المنبع',
    'certificate_label' => 'شهادة ضريبة الخصم من المنبع',
    'certificates_label' => 'شهادات ضريبة الخصم من المنبع',

    // Basic Information
    'basic_information' => 'المعلومات الأساسية',
    'name' => 'الاسم',
    'rate' => 'النسبة (%)',
    'rate_help' => 'أدخل النسبة المئوية (مثلاً، 5 تعني 5%)',
    'withholding_account' => 'حساب ضريبة الخصم من المنبع',
    'applicable_to' => 'يطبق على',
    'threshold_amount' => 'مبلغ الحد الأدنى',
    'threshold_help' => 'الحد الأدنى لمبلغ الدفع قبل تطبيق ضريبة الخصم (اتركه فارغاً لعدم وجود حد)',
    'is_active' => 'نشط',

    // Certificate fields
    'certificate_number' => 'رقم الشهادة',
    'vendor' => 'المورد',
    'certificate_date' => 'تاريخ الشهادة',
    'period_start' => 'بداية الفترة',
    'period_end' => 'نهاية الفترة',
    'total_base_amount' => 'إجمالي المبلغ الأساسي',
    'total_withheld_amount' => 'إجمالي المبلغ المخصوم',
    'status' => 'الحالة',
    'notes' => 'ملاحظات',

    // Entry fields
    'payment' => 'الدفعة',
    'base_amount' => 'المبلغ الأساسي',
    'withheld_amount' => 'المبلغ المخصوم',
    'rate_applied' => 'النسبة المطبقة',
    'certificate' => 'الشهادة',

    // Timestamps
    'created_at' => 'تم الإنشاء في',
    'updated_at' => 'تم التحديث في',

    // Pages
    'pages' => [
        'list' => 'أنواع ضريبة الخصم من المنبع',
        'create' => 'إنشاء نوع ضريبة خصم',
        'edit' => 'تعديل نوع ضريبة خصم',
        'list_certificates' => 'شهادات ضريبة الخصم من المنبع',
        'create_certificate' => 'إنشاء شهادة',
        'view_certificate' => 'عرض الشهادة',
    ],

    // Report
    'report' => [
        'title' => 'تقرير ضريبة الخصم من المنبع',
        'by_vendor' => 'حسب المورد',
        'by_type' => 'حسب النوع',
        'uncertified_entries' => 'قيود غير معتمدة',
        'total_certificates' => 'إجمالي الشهادات',
    ],
];
