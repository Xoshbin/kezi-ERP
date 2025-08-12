<?php

return [
    // Labels
    'label' => 'شريك',
    'plural_label' => 'شركاء',

    // Form Sections
    'basic_information' => 'المعلومات الأساسية',
    'basic_information_description' => 'تفاصيل الشريك الأساسية ونوع العمل',
    'contact_information' => 'معلومات الاتصال',
    'contact_information_description' => 'تفاصيل التواصل مع هذا الشريك',
    'address_information' => 'معلومات العنوان',
    'address_information_description' => 'الموقع الفعلي وعنوان المراسلة',
    'accounting_configuration' => 'إعداد المحاسبة',
    'accounting_configuration_description' => 'تخصيص الحسابات للمعاملات المالية',

    // Basic Information
    'company' => 'شركة',
    'name' => 'اسم',
    'type' => 'نوع',
    'contact_person' => 'شخص الاتصال',
    'email' => 'بريد إلكتروني',
    'phone' => 'هاتف',
    'tax_id' => 'الرقم الضريبي',
    'is_active' => 'نشط',
    'receivable_account' => 'حساب المدينين',
    'payable_account' => 'حساب الدائنين',
    'receivable_account_help' => 'الحساب المستخدم لتتبع الأموال التي يدين بها هذا الشريك لنا (فواتير العملاء)',
    'payable_account_help' => 'الحساب المستخدم لتتبع الأموال التي ندين بها لهذا الشريك (فواتير الموردين)',
    'create_receivable_account' => 'إنشاء حساب المدينين',
    'create_payable_account' => 'إنشاء حساب الدائنين',

    // Address
    'address' => 'عنوان',
    'address_line_1' => 'سطر العنوان 1',
    'address_line_2' => 'سطر العنوان 2',
    'city' => 'مدينة',
    'state' => 'محافظة',
    'zip_code' => 'رمز بريدي',
    'country' => 'بلد',

    // Financial Information
    'customer_outstanding' => 'مستحقات العميل',
    'customer_overdue' => 'متأخرات العميل',
    'vendor_outstanding' => 'مستحقات المورد',
    'vendor_overdue' => 'متأخرات المورد',
    'last_activity' => 'آخر نشاط',
    'no_activity' => 'لا يوجد نشاط',
    'has_overdue_amounts' => 'لديه مبالغ متأخرة',
    'has_outstanding_balance' => 'لديه رصيد مستحق',

    // Widget Labels
    'widgets' => [
        // Common
        'includes_overdue' => 'يشمل :amount متأخر',
        'days' => 'أيام',
        'immediate_attention' => 'يحتاج انتباه فوري',
        'payment_performance' => 'أداء الدفع',
        'current_month_activity' => 'نشاط الشهر الحالي',
        'urgent_payments' => 'مدفوعات عاجلة',
        'our_payment_performance' => 'أداء دفعنا',
        'last_month_payments' => 'مدفوعات الشهر الماضي',

        // Customer Widgets
        'total_outstanding' => 'إجمالي المستحق',
        'due_within_7_days' => 'مستحق خلال 7 أيام',
        'average_payment_time' => 'متوسط وقت الدفع',
        'received_this_month' => 'مستلم هذا الشهر',

        // Vendor Widgets
        'total_to_pay' => 'إجمالي المطلوب دفعه',
        'paid_last_month' => 'مدفوع الشهر الماضي',

        // Legacy (keeping for compatibility)
        'customer_owes_us' => 'العميل يدين لنا',
        'overdue_amount' => 'مبلغ متأخر',
        'no_overdue_amounts' => 'لا توجد مبالغ متأخرة',
        'requires_attention' => 'يتطلب انتباه',
        'due_within_30_days' => 'مستحق خلال 30 يوم',
        'upcoming_collections' => 'تحصيلات قادمة',
        'avg_payment_days' => 'متوسط أيام الدفع',
        'no_payment_history' => 'لا يوجد تاريخ دفع',
        'average_collection_time' => 'متوسط وقت التحصيل',
        'last_payment' => 'آخر دفعة',
        'no_payments' => 'لا توجد دفعات',
        'no_payment_received' => 'لم يتم استلام دفعة',
        'total_payable' => 'إجمالي المستحق الدفع',
        'we_owe_vendor' => 'ندين للمورد',
        'overdue_payable' => 'مستحق الدفع متأخر',
        'no_overdue_payments' => 'لا توجد مدفوعات متأخرة',
        'payment_required' => 'مطلوب دفع',
        'pay_within_7_days' => 'ادفع خلال 7 أيام',
        'urgent_payments_needed' => 'مطلوب مدفوعات عاجلة',
        'pay_within_30_days' => 'ادفع خلال 30 يوم',
        'upcoming_payments' => 'مدفوعات قادمة',
        'our_avg_payment_days' => 'متوسط أيام دفعنا',
        'our_payment_time' => 'وقت دفعنا',
        'last_payment_made' => 'آخر دفعة تمت',
        'no_payment_made' => 'لم يتم دفع',

        // Overview Widgets
        'lifetime_value' => 'القيمة مدى الحياة',
        'total_business_volume' => 'إجمالي حجم الأعمال',
        'this_month' => 'هذا الشهر',
        'no_activity_this_month' => 'لا يوجد نشاط هذا الشهر',
        'current_month_volume' => 'حجم الشهر الحالي',
        'performance_score' => 'نقاط الأداء',
        'excellent_performance' => 'أداء ممتاز',
        'good_performance' => 'أداء جيد',
        'average_performance' => 'أداء متوسط',
        'poor_performance' => 'أداء ضعيف',
        'very_poor_performance' => 'أداء ضعيف جداً',
        'last_activity' => 'آخر نشاط',
        'today' => 'اليوم',
        'days_ago' => 'منذ :days أيام',
        'no_activity' => 'لا يوجد نشاط',
        'no_transactions' => 'لا توجد معاملات مسجلة',
        'partner_type' => 'نوع الشريك',
        'customer_only' => 'عميل فقط',
        'vendor_only' => 'مورد فقط',
        'customer_and_vendor' => 'عميل ومورد',
        'status' => 'حالة',
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'active_partner' => 'شريك نشط',
        'inactive_partner' => 'شريك غير نشط',
    ],

    // Timestamps
    'created_at' => 'تاريخ الإنشاء',
    'updated_at' => 'تاريخ التحديث',
    'deleted_at' => 'تاريخ الحذف',

    // Relation Managers
    'invoices_relation_manager' => [
        'title' => 'فواتير',
        'invoice_number' => 'رقم الفاتورة',
        'invoice_date' => 'تاريخ الفاتورة',
        'due_date' => 'تاريخ الاستحقاق',
        'status' => 'حالة',
        'total_amount' => 'المبلغ الإجمالي',
    ],
    'vendor_bills_relation_manager' => [
        'title' => 'فواتير الموردين',
        'bill_reference' => 'مرجع الفاتورة',
        'bill_date' => 'تاريخ الفاتورة',
        'accounting_date' => 'تاريخ المحاسبة',
        'due_date' => 'تاريخ الاستحقاق',
        'status' => 'حالة',
        'total_amount' => 'المبلغ الإجمالي',
    ],
    'payments_relation_manager' => [
        'title' => 'مدفوعات',
        'payment_date' => 'تاريخ الدفع',
        'amount' => 'مبلغ',
        'payment_type' => 'نوع الدفع',
        'reference' => 'مرجع',
        'status' => 'حالة',
    ],
];
