<?php

return [
    'label' => 'طلب عرض سعر',
    'plural_label' => 'طلبات عروض الأسعار',
    'navigation_label' => 'طلبات عروض الأسعار',
    'fields' => [
        'rfq_number' => 'رقم الطلب',
        'vendor' => 'المورد',
        'company' => 'الشركة',
        'rfq_date' => 'تاريخ الطلب',
        'valid_until' => 'صالح حتى',
        'currency' => 'العملة',
        'exchange_rate' => 'سعر الصرف',
        'status' => 'الحالة',
        'subtotal' => 'المجموع الفرعي',
        'tax_total' => 'إجمالي الضريبة',
        'total' => 'الإجمالي',
        'total_company_currency' => 'الإجمالي (بعملة الشركة)',
        'date' => 'التاريخ',
    ],
    'sections' => [
        'general' => 'معلومات عامة',
        'basic_info' => 'معلومات أساسية',
        'vendor_info' => 'تفاصيل المورد',
        'line_items' => 'البنود',
        'totals' => 'الإجماليات',
        'details' => 'التفاصيل',
        'notes' => 'ملاحظات',
    ],
    'lines' => [
        'product' => 'المنتج',
        'description' => 'الوصف',
        'quantity' => 'الكمية',
        'unit' => 'الوحدة',
        'unit_price' => 'سعر الوحدة',
        'tax' => 'الضريبة',
    ],
];
