<?php

return [
    'singular' => 'شركة',
    'plural' => 'شركات',
    'name' => 'اسم',
    'address' => 'عنوان',
    'tax_id' => 'الرقم الضريبي',
    'currency_id' => 'عملة',
    'fiscal_country' => 'البلد الضريبي',
    'parent_company_id' => 'الشركة الأم',
    'default_accounts_payable' => 'حساب الدائنين الافتراضي',
    'default_tax_receivable' => 'حساب الضريبة المستحقة الافتراضي',
    'default_purchase_journal' => 'دفتر المشتريات الافتراضي',
    'default_accounts_receivable' => 'حساب المدينين الافتراضي',
    'default_sales_discount_account' => 'حساب خصم المبيعات الافتراضي',
    'default_tax_account' => 'حساب الضريبة الافتراضي',
    'default_sales_journal' => 'دفتر المبيعات الافتراضي',
    'default_depreciation_journal' => 'دفتر الإهلاك الافتراضي',
    'default_bank_account' => 'الحساب البنكي الافتراضي',
    'default_outstanding_receipts_account' => 'حساب الإيصالات المعلقة الافتراضي',
    'created_at' => 'تاريخ الإنشاء',
    'updated_at' => 'تاريخ التحديث',

    'accounts' => [
        'title' => 'حسابات',
        'code' => 'رمز',
        'name' => 'اسم',
        'type' => 'نوع',
        'is_deprecated' => 'مهجور',
    ],

    'users' => [
        'title' => 'مستخدمون',
        'name' => 'اسم',
        'email' => 'بريد إلكتروني',
        'email_verified_at' => 'تاريخ تأكيد البريد الإلكتروني',
        'password' => 'كلمة مرور',
    ],

    // Sections
    'section' => [
        'details' => 'تفاصيل الشركة',
        'defaults' => 'إعدادات الشركة الافتراضية',
    ],

    'enable_reconciliation' => 'Enable Reconciliation',
    'enable_reconciliation_help' => 'Enable reconciliation functionality for this company. When disabled, all reconciliation features will be hidden.',
];
