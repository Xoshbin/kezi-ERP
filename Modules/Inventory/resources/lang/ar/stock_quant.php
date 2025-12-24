<?php

return [
    'label' => 'كمية المخزون',
    'plural_label' => 'كميات المخزون',

    'sections' => [
        'basic_info' => 'المعلومات الأساسية',
        'quantities' => 'الكميات',
    ],

    'fields' => [
        'id' => 'المعرف',
        'product' => 'المنتج',
        'location' => 'الموقع',
        'lot' => 'الدفعة',
        'quantity' => 'الكمية',
        'reserved_quantity' => 'الكمية المحجوزة',
        'available_quantity' => 'الكمية المتاحة',
        'updated_at' => 'آخر تحديث',
    ],

    'filters' => [
        'product' => 'المنتج',
        'location' => 'الموقع',
        'lot' => 'الدفعة',
        'low_stock' => 'مخزون منخفض (≤ 10)',
        'out_of_stock' => 'نفاد المخزون',
        'with_reservations' => 'مع حجوزات',
    ],

    'no_lot' => 'بدون دفعة',

    'empty_state' => [
        'heading' => 'لم يتم العثور على كميات مخزون',
        'description' => 'ستظهر كميات المخزون هنا عندما يكون للمنتجات مخزون في المواقع.',
    ],
];
