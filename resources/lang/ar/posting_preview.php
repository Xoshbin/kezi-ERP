<?php

return [
    'preview_posting' => 'معاينة القيود',
    'posting_preview' => 'معاينة القيد المحاسبي',
    'export_preview_csv' => 'تصدير المعاينة (CSV)',
    'export_preview_pdf' => 'تصدير المعاينة (PDF)',

    'errors_title' => 'الأخطاء',

    'links' => [
        'fix_in_accounts' => 'إصلاح في الحسابات',
        'fix_input_tax' => 'إصلاح ضريبة المدخلات',
        'open_company' => 'فتح الشركة',
        'open_product' => 'فتح المنتج',
        'open_assets' => 'فتح الأصول',
        'open_accounts' => 'فتح الحسابات',
        'open_taxes' => 'فتح الضرائب',
    ],

    'table' => [
        'account' => 'الحساب',
        'description' => 'الوصف',
        'debit' => 'مدين',
        'credit' => 'دائن',
        'totals' => 'الإجمالي',
        'debits' => 'مدين',
        'credits' => 'دائن',
        'balanced' => 'متزن',
        'unbalanced' => 'غير متزن',
    ],

    'pdf' => [
        'vendor_bill_heading' => 'معاينة قيود فاتورة المورد',
        'invoice_heading' => 'معاينة قيود الفاتورة',
        'adjustment_heading' => 'معاينة قيود التسوية',
    ],

    'lines' => [
        'inventory' => 'المخزون: ',
        'asset' => 'الأصل: ',
        'input_tax' => 'ضريبة المدخلات: ',
        'revenue' => 'الإيراد: ',
        'output_tax' => 'ضريبة المخرجات: ',
        'ap' => 'الدائنون',
        'ar' => 'المدينون',
        'sales_discount' => 'حسم المبيعات/عكس الإيراد',
        'tax_payable' => 'ضريبة مستحقة',
    ],

    'errors' => [
        'ap_account_missing' => 'حساب الدائنين الافتراضي غير مُعد للشركة.',
        'purchase_journal_missing' => 'دفتر يومية المشتريات الافتراضي غير مُعد للشركة.',
        'inventory_account_missing' => 'المنتج رقم :product_id لا يملك حساب مخزون.',
        'asset_category_invalid' => 'فئة أصل غير صالحة في أحد أسطر الفاتورة.',
        'input_tax_missing' => 'لا يوجد حساب ضريبة مدخلات مع أن هناك أسطر خاضعة للضريبة.',

        'ar_account_missing' => 'حساب المدينين الافتراضي غير مُعد للشركة.',
        'sales_journal_missing' => 'دفتر يومية المبيعات الافتراضي غير مُعد للشركة.',
        'income_account_missing' => 'لا يوجد حساب إيراد لأحد أسطر الفاتورة.',
        'tax_account_missing' => 'الضريبة المختارة لا تحتوي على حساب.',

        'sales_discount_missing' => 'حساب حسم المبيعات الافتراضي غير مُعد للشركة.',
        'tax_payable_missing' => 'حساب الضريبة المستحقة الافتراضي غير مُعد للشركة.',
    ],
];

