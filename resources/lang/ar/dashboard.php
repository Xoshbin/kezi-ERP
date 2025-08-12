<?php

return [
    'title' => 'لوحة التحكم',
    'financial_dashboard' => 'لوحة التحكم المالية',
    'no_company' => 'لا توجد شركة',
    'welcome_message' => 'مرحباً بك في :company - :date',

    'financial' => [
        // Stats Overview
        'current_month_profit' => 'ربح الشهر الحالي',
        'ytd_profit' => 'ربح من بداية السنة',
        'total_receivables' => 'إجمالي المدينين',
        'total_payables' => 'إجمالي الدائنين',
        'cash_balance' => 'رصيد النقد',
        'gross_margin' => 'الهامش الإجمالي',

        // Descriptions
        'profit_after_expenses' => 'الربح بعد جميع المصروفات',
        'year_to_date_performance' => 'الأداء من بداية السنة',
        'outstanding_customer_invoices' => 'فواتير العملاء المستحقة',
        'outstanding_vendor_bills' => 'فواتير الموردين المستحقة',
        'total_cash_all_accounts' => 'إجمالي النقد في جميع الحسابات',
        'profitability_ratio' => 'نسبة الربحية',

        // Chart
        'income_vs_expense_chart' => 'اتجاه الإيراد مقابل المصروف (آخر 12 شهر)',
        'total_revenue' => 'إجمالي الإيراد',
        'total_expenses' => 'إجمالي المصروفات',
        'net_income' => 'صافي الدخل',
        'month' => 'شهر',
        'amount' => 'مبلغ',
        'no_data' => 'لا توجد بيانات متاحة',

        // Error states
        'error' => 'خطأ',
        'data_unavailable' => 'البيانات المالية غير متاحة',
        'please_check_setup' => 'يرجى التحقق من إعداد المحاسبة',
    ],

    'cash_flow' => [
        'overdue_receivables' => 'مدينون متأخرون',
        'overdue_payables' => 'دائنون متأخرون',
        'forecast_near_term' => 'توقعات النقد قريبة المدى',
        'forecast_30_days' => 'توقعات النقد لـ 30 يوم',

        // Descriptions
        'immediate_collection_needed' => 'مطلوب تحصيل فوري',
        'immediate_payment_needed' => 'مطلوب دفع فوري',
        'net_cash_flow_soon' => 'صافي التدفق النقدي (الحالي + 30 يوم)',
        'net_cash_flow_month' => 'صافي التدفق النقدي (الحالي + 60 يوم)',

        // Error states
        'error' => 'خطأ',
        'data_unavailable' => 'بيانات التدفق النقدي غير متاحة',
        'please_check_setup' => 'يرجى التحقق من إعداد المحاسبة',
    ],

    'accounts' => [
        'total_accounts' => 'إجمالي الحسابات',
        'asset_accounts' => 'حسابات الأصول',
        'liability_accounts' => 'حسابات الالتزامات',
        'income_accounts' => 'حسابات الإيراد',
        'expense_accounts' => 'حسابات المصروفات',

        // Descriptions
        'chart_of_accounts' => 'دليل الحسابات',
        'assets_and_cash' => 'الأصول والنقد',
        'debts_and_obligations' => 'الديون والالتزامات',
        'revenue_sources' => 'مصادر الإيراد',
        'cost_categories' => 'فئات التكلفة',
    ],
];
