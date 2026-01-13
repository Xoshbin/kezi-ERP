<?php

return [
    'navigation' => [
        'name' => 'فاتورة المواد',
        'plural' => 'فواتير المواد',
        'group' => 'التصنيع',
    ],
    'fields' => [
        'product' => 'المنتج النهائي',
        'code' => 'رمز الفاتورة',
        'name' => 'اسم الفاتورة',
        'type' => 'نوع الفاتورة',
        'quantity' => 'الكمية للإنتاج',
        'is_active' => 'نشط',
        'notes' => 'ملاحظات',
        'components' => 'المكونات',
        'qty' => 'الكمية',
        'created' => 'تاريخ الإنشاء',
    ],
    'sections' => [
        'info' => 'معلومات الفاتورة',
    ],
    'types' => [
        'normal' => 'عادي',
        'kit' => 'طقم',
        'phantom' => 'وهمي',
    ],
];
