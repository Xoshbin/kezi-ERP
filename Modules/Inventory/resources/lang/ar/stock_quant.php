<?php

return [
    'label' => 'كمية المخزون',
    'plural_label' => 'كميات المخزون',

    'sections' => [
        'basic_info' => 'معلومات أساسية',
        'quantities' => 'الكميات',
    ],

    'fields' => [
        'id' => 'المعرف',
        'product' => 'المنتج',
        'location' => 'الموقع',
        'lot' => 'رقم الدفعة',
        'quantity' => 'الكمية',
        'reserved_quantity' => 'الكمية المحجوزة',
        'available_quantity' => 'الكمية المتاحة',
        'updated_at' => 'آخر تحديث',
    ],

    'filters' => [
        'product' => 'المنتج',
        'location' => 'الموقع',
        'lot' => 'رقم الدفعة',
        'low_stock' => 'مخزون منخفض (≤ 10)',
        'out_of_stock' => 'نفذ المخزون',
        'with_reservations' => 'مع حجوزات',
    ],

    'no_lot' => 'لا يوجد رقم دفعة',

    'empty_state' => [
        'heading' => 'لم يتم العثور على كميات المخزون',
        'description' => 'ستظهر كميات المخزون هنا عندما يكون للمنتجات مخزون في المواقع.',
    ],
];
