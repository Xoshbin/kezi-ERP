<?php

return [
    // Navigation and Page Titles
    'navigation_label' => 'لوحة التحكم',
    'title' => 'لوحة تحكم المخزون',
    'heading' => 'نظرة عامة على المخزون',
    'subheading' => 'راقب أداء المخزون والمؤشرات الرئيسية',

    // Filters
    'filters' => [
        'date_from' => 'من تاريخ',
        'date_to' => 'إلى تاريخ',
        'location' => 'الموقع',
        'products' => 'المنتجات',
    ],

    // Stats Overview
    'stats' => [
        'total_value' => 'إجمالي قيمة المخزون',
        'total_value_description' => 'القيمة الحالية لجميع المخزون',

        'turnover_ratio' => 'معدل دوران المخزون',
        'turnover_description' => 'معدل دوران المخزون السنوي',

        'low_stock' => 'المواد منخفضة المخزون',
        'low_stock_description' => 'المنتجات أقل من الحد الأدنى',

        'expiring_lots' => 'الدفعات منتهية الصلاحية',
        'expiring_lots_description' => 'الدفعات التي تنتهي صلاحيتها خلال 30 يوماً',
    ],

    // Charts
    'charts' => [
        'inventory_value' => [
            'title' => 'اتجاه قيمة المخزون',
            'description' => 'تتبع تغيرات قيمة المخزون عبر الزمن',
            'dataset_label' => 'قيمة المخزون',
        ],

        'turnover' => [
            'title' => 'الاستلام مقابل التسليم',
            'description' => 'مقارنة أسبوعية لحركات المخزون',
            'receipts_label' => 'الاستلام',
            'deliveries_label' => 'التسليم',
        ],

        'aging' => [
            'title' => 'تقادم المخزون',
            'description' => 'توزيع المخزون حسب العمر',
            'quantity_label' => 'الكمية',
        ],
    ],

    // Quick Actions
    'quick_actions' => [
        'new_receipt' => [
            'title' => 'استلام جديد',
            'description' => 'تسجيل مخزون وارد',
            'button' => 'إنشاء استلام',
        ],

        'new_delivery' => [
            'title' => 'تسليم جديد',
            'description' => 'تسجيل مخزون صادر',
            'button' => 'إنشاء تسليم',
        ],

        'reports' => [
            'title' => 'عرض التقارير',
            'description' => 'الوصول إلى تقارير المخزون التفصيلية',
            'button' => 'عرض التقارير',
        ],
    ],
];
