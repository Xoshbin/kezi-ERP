<?php

return [
    'valuation' => [
        'navigation_label' => 'تقييم المخزون',
        'title' => 'تقرير تقييم المخزون',
        'heading' => 'تقرير تقييم المخزون',

        'filters' => [
            'title' => 'فلاتر التقرير',
            'as_of_date' => 'حتى تاريخ',
            'products' => 'المنتجات',
            'include_reconciliation' => 'تضمين مطابقة دفتر الأستاذ',
            'valuation_method' => 'طريقة التقييم',
        ],

        'summary' => [
            'total_value' => 'إجمالي قيمة المخزون',
            'total_quantity' => 'إجمالي الكمية',
            'product_count' => 'المنتجات',
            'as_of_date' => 'حتى تاريخ',
        ],

        'reconciliation' => [
            'title' => 'مطابقة دفتر الأستاذ',
            'gl_balance' => 'رصيد حساب دفتر الأستاذ',
            'calculated_value' => 'القيمة المحسوبة',
            'difference' => 'الفرق',
            'reconciled' => 'مطابق',
            'not_reconciled' => 'غير مطابق',
        ],

        'table' => [
            'title' => 'تفاصيل تقييم المنتجات',
            'product' => 'المنتج',
            'valuation_method' => 'الطريقة',
            'quantity' => 'الكمية',
            'unit_cost' => 'تكلفة الوحدة',
            'total_value' => 'إجمالي القيمة',
            'cost_layers' => 'طبقات التكلفة',
        ],

        'actions' => [
            'export' => 'تصدير',
            'refresh' => 'تحديث',
            'view_cost_layers' => 'عرض طبقات التكلفة',
        ],

        'cost_layers_modal' => [
            'title' => 'طبقات التكلفة',
            'purchase_date' => 'تاريخ الشراء',
            'quantity' => 'الكمية',
            'cost_per_unit' => 'التكلفة لكل وحدة',
            'total_value' => 'إجمالي القيمة',
            'total' => 'الإجمالي',
            'weighted_avg' => 'المتوسط المرجح',
            'no_layers' => 'لا توجد طبقات تكلفة',
            'no_layers_description' => 'يستخدم هذا المنتج طريقة AVCO للتقييم أو ليس لديه حركات مخزون.',
        ],

        'export_started' => 'بدأ التصدير بنجاح.',
        'no_data' => 'لم يتم العثور على بيانات المخزون',
        'no_data_description' => 'لا توجد منتجات لديها مخزون للمعايير المحددة.',
    ],

    'aging' => [
        'navigation_label' => 'تقادم المخزون',
        'title' => 'تقرير تقادم المخزون',
        'heading' => 'تقرير تقادم المخزون',

        'filters' => [
            'title' => 'فلاتر التقرير',
            'products' => 'المنتجات',
            'locations' => 'المواقع',
            'include_expiration' => 'تضمين تحليل انتهاء الصلاحية',
            'expiration_warning_days' => 'أيام تحذير انتهاء الصلاحية',
        ],

        'summary' => [
            'total_value' => 'إجمالي قيمة المخزون',
            'total_quantity' => 'إجمالي الكمية',
            'average_age' => 'متوسط العمر (أيام)',
            'expiring_soon' => 'ينتهي قريباً',
        ],

        'buckets' => [
            'title' => 'توزيع العمر',
            'age_range' => 'نطاق العمر',
            'quantity' => 'الكمية',
            'value' => 'القيمة',
            'percentage' => 'النسبة المئوية',
            'products' => 'المنتجات',
            'total' => 'الإجمالي',
        ],

        'expiration' => [
            'title' => 'الدفعات المنتهية الصلاحية',
            'lot_code' => 'رمز الدفعة',
            'product' => 'المنتج',
            'expiration_date' => 'تاريخ انتهاء الصلاحية',
            'days_until_expiration' => 'أيام حتى الانتهاء',
            'quantity_on_hand' => 'الكمية المتوفرة',
        ],

        'days' => 'أيام',
        'days_ago' => 'أيام مضت',
        'expired' => 'منتهي الصلاحية',
        'export_started' => 'بدأ التصدير بنجاح.',
        'export_failed' => 'فشل التصدير',
        'no_data_to_export' => 'لا توجد بيانات للتصدير',
        'export_confirmation' => 'تصدير تقرير تقادم المخزون',
        'export_description' => 'سيتم إنشاء ملف CSV يحتوي على تحليل تقادم المخزون. سيتم تنزيل الملف إلى جهازك.',
        'actions' => [
            'export' => 'تصدير',
            'refresh' => 'تحديث',
        ],
        'no_data' => 'لم يتم العثور على بيانات تقادم',
        'no_data_description' => 'لم يتم العثور على مخزون للمعايير المحددة.',
    ],

    'turnover' => [
        'navigation_label' => 'دوران المخزون',
        'title' => 'تقرير دوران المخزون',
        'heading' => 'تقرير دوران المخزون',

        'filters' => [
            'title' => 'فلاتر التقرير',
            'start_date' => 'تاريخ البداية',
            'end_date' => 'تاريخ النهاية',
            'products' => 'المنتجات',
        ],

        'summary' => [
            'total_cogs' => 'إجمالي تكلفة البضاعة المباعة',
            'average_inventory' => 'متوسط قيمة المخزون',
            'turnover_ratio' => 'نسبة الدوران',
            'days_sales_inventory' => 'أيام مبيعات المخزون',
        ],

        'analysis' => [
            'title' => 'تحليل الدوران',
            'excellent' => 'ممتاز (>12x)',
            'good' => 'جيد (6-12x)',
            'average' => 'متوسط (3-6x)',
            'poor' => 'ضعيف (<3x)',
            'ratio_explanation' => 'يدور مخزونك :ratio مرات خلال هذه الفترة.',
        ],

        'benchmarks' => [
            'excellent' => 'يدور المخزون أكثر من 12 مرة في السنة',
            'good' => 'يدور المخزون 6-12 مرة في السنة',
            'average' => 'يدور المخزون 3-6 مرات في السنة',
            'poor' => 'يدور المخزون أقل من 3 مرات في السنة',
        ],

        'period_info' => [
            'title' => 'معلومات الفترة',
            'start_date' => 'تاريخ البداية',
            'end_date' => 'تاريخ النهاية',
            'period_length' => 'طول الفترة',
        ],

        'days' => 'أيام',
        'annualized' => 'سنوياً',
        'actions' => [
            'export' => 'تصدير',
            'refresh' => 'تحديث',
        ],
        'export_started' => 'بدأ التصدير بنجاح.',
        'export_failed' => 'فشل التصدير',
        'no_data_to_export' => 'لا توجد بيانات للتصدير',
        'no_data' => 'لم يتم العثور على بيانات الدوران',
        'no_data_description' => 'لم يتم العثور على تكلفة بضاعة مباعة أو حركات مخزون للفترة المحددة.',
    ],

    'lot_trace' => [
        'navigation_label' => 'تتبع الدفعات',
        'title' => 'تقرير تتبع الدفعات',
        'heading' => 'تقرير تتبع الدفعات',

        'filters' => [
            'title' => 'معايير البحث',
            'product' => 'المنتج',
            'lot' => 'الدفعة',
        ],

        'summary' => [
            'title' => 'ملخص الدفعة',
            'lot_code' => 'رمز الدفعة',
            'product' => 'المنتج',
            'expiration_date' => 'تاريخ انتهاء الصلاحية',
            'current_quantity' => 'الكمية الحالية',
            'total_value' => 'إجمالي القيمة',
        ],

        'movements' => [
            'title' => 'سجل الحركات',
            'date' => 'التاريخ',
            'type' => 'النوع',
            'quantity' => 'الكمية',
            'from_location' => 'من الموقع',
            'to_location' => 'إلى الموقع',
            'reference' => 'المرجع',
            'journal_entry' => 'القيد اليومي',
            'valuation_amount' => 'مبلغ التقييم',
            'incoming' => 'الحركات الواردة',
            'outgoing' => 'الحركات الصادرة',
            'internal' => 'الحركات الداخلية',
            'count' => 'حركات',
        ],

        'actions' => [
            'export' => 'تصدير',
            'refresh' => 'تحديث',
        ],

        'no_expiration' => 'بدون انتهاء صلاحية',
        'export_started' => 'بدأ التصدير بنجاح.',
        'export_failed' => 'فشل التصدير',
        'no_data_to_export' => 'لا توجد بيانات للتصدير',
        'no_selection' => 'اختر المنتج والدفعة',
        'no_selection_description' => 'الرجاء اختيار منتج ودفعة لعرض معلومات التتبع.',
        'no_movements' => 'لم يتم العثور على حركات',
        'no_movements_description' => 'هذه الدفعة ليس لديها حركات مسجلة في النظام.',
    ],

    'reorder' => [
        'navigation_label' => 'حالة إعادة الطلب',
        'title' => 'تقرير حالة إعادة الطلب',
        'heading' => 'تقرير حالة إعادة الطلب',

        'filters' => [
            'title' => 'فلاتر التقرير',
            'products' => 'المنتجات',
            'locations' => 'المواقع',
            'include_suggested_orders' => 'تضمين الطلبات المقترحة',
            'include_overstock' => 'تضمين المخزون الزائد',
        ],

        'summary' => [
            'critical' => 'العناصر الحرجة',
            'low_stock' => 'مخزون منخفض',
            'suggested' => 'الطلبات المقترحة',
            'overstock' => 'المخزون الزائد',
            'suggested_value' => 'القيمة المقترحة',
        ],

        'alerts' => [
            'critical_title' => 'تنبيه مخزون حرج',
            'critical_description' => ':count منتجات منخفضة بشكل حرج وتحتاج اهتماماً فورياً.',
        ],

        'table' => [
            'title' => 'حالة إعادة الطلب',
            'product' => 'المنتج',
            'location' => 'الموقع',
            'current_quantity' => 'الكمية الحالية',
            'min_quantity' => 'الحد الأدنى',
            'max_quantity' => 'الحد الأقصى',
            'suggested_quantity' => 'الكمية المقترحة',
            'status' => 'الحالة',
            'estimated_cost' => 'التكلفة التقديرية',
        ],

        'actions' => [
            'export' => 'تصدير',
            'refresh' => 'تحديث',
        ],

        'export_started' => 'بدأ التصدير بنجاح.',
        'export_failed' => 'فشل التصدير',
        'no_data_to_export' => 'لا توجد بيانات للتصدير',
        'no_data' => 'لم يتم العثور على بيانات إعادة الطلب',
        'no_data_description' => 'لم يتم العثور على منتجات مع قواعد إعادة الطلب للمعايير المحددة.',
    ],
];
