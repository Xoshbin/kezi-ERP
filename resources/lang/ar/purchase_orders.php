<?php

return [
    'label' => 'أمر الشراء',
    'plural_label' => 'أوامر الشراء',

    'navigation' => [
        'label' => 'أوامر الشراء',
        'group' => 'المشتريات',
    ],

    'sections' => [
        'basic_info' => 'المعلومات الأساسية',
        'vendor_details' => 'تفاصيل المورد',
        'delivery_info' => 'معلومات التسليم',
        'notes' => 'الملاحظات والشروط',
        'totals' => 'الإجماليات',
        'lines' => 'بنود الطلب',
    ],

    'fields' => [
        'id' => 'المعرف',
        'po_number' => 'رقم أمر الشراء',
        'status' => 'الحالة',
        'reference' => 'المرجع',
        'vendor' => 'المورد',
        'currency' => 'العملة',
        'po_date' => 'تاريخ أمر الشراء',
        'expected_delivery_date' => 'تاريخ التسليم المتوقع',
        'confirmed_at' => 'تم التأكيد في',
        'cancelled_at' => 'تم الإلغاء في',
        'exchange_rate_at_creation' => 'سعر الصرف',
        'total_amount' => 'المبلغ الإجمالي',
        'total_tax' => 'إجمالي الضريبة',
        'total_amount_company_currency' => 'الإجمالي (عملة الشركة)',
        'total_tax_company_currency' => 'الضريبة (عملة الشركة)',
        'notes' => 'الملاحظات',
        'terms_and_conditions' => 'الشروط والأحكام',
        'delivery_location' => 'موقع التسليم',
        'created_by_user' => 'أنشأ بواسطة',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
        'billing_status' => 'حالة الفوترة',
    ],

    'line_fields' => [
        'product' => 'المنتج',
        'description' => 'الوصف',
        'quantity' => 'الكمية',
        'quantity_received' => 'الكمية المستلمة',
        'remaining_quantity' => 'الكمية المتبقية',
        'unit_price' => 'سعر الوحدة',
        'subtotal' => 'المجموع الفرعي',
        'tax' => 'الضريبة',
        'total_line_tax' => 'ضريبة البند',
        'total' => 'الإجمالي',
        'expected_delivery_date' => 'التسليم المتوقع',
        'notes' => 'الملاحظات',
    ],

    'actions' => [
        'create' => 'إنشاء أمر شراء',
        'edit' => 'تعديل أمر الشراء',
        'view' => 'عرض أمر الشراء',
        'send_rfq' => 'إرسال طلب عرض أسعار',
        'send' => 'إرسال للمورد',
        'confirm' => 'تأكيد أمر الشراء',
        'mark_done' => 'تحديد كمكتمل',
        'cancel' => 'إلغاء أمر الشراء',
        'receive_goods' => 'استلام البضائع',
        'create_bill' => 'إنشاء فاتورة مورد',
        'add_line' => 'إضافة بند',
        'remove_line' => 'إزالة بند',
    ],

    'messages' => [
        'created' => 'تم إنشاء أمر الشراء بنجاح.',
        'updated' => 'تم تحديث أمر الشراء بنجاح.',
        'confirmed' => 'تم تأكيد أمر الشراء بنجاح.',
        'cancelled' => 'تم إلغاء أمر الشراء بنجاح.',
        'cannot_edit_confirmed' => 'لا يمكن تعديل أمر شراء مؤكد.',
        'cannot_confirm_without_lines' => 'لا يمكن تأكيد أمر الشراء بدون بنود.',
        'cannot_cancel_completed' => 'لا يمكن إلغاء أمر شراء مكتمل.',
        'fully_received' => 'تم استلام جميع العناصر لأمر الشراء هذا.',
        'partially_received' => 'تم استلام بعض العناصر لأمر الشراء هذا.',
    ],

    'notifications' => [
        'rfq_sent' => 'تم إرسال طلب عرض الأسعار للمورد بنجاح.',
        'sent' => 'تم إرسال أمر الشراء للمورد بنجاح.',
        'confirmed' => 'تم تأكيد أمر الشراء بنجاح.',
        'marked_done' => 'تم تحديد أمر الشراء كمكتمل بنجاح.',
        'cancelled' => 'تم إلغاء أمر الشراء بنجاح.',
    ],

    'status' => [
        // Pre-commitment phase
        'rfq' => 'طلب عرض أسعار',
        'rfq_sent' => 'تم إرسال طلب عرض الأسعار',

        // Commitment phase
        'draft' => 'مسودة',
        'sent' => 'مرسل',
        'confirmed' => 'مؤكد',

        // Fulfillment phase
        'to_receive' => 'للاستلام',
        'partially_received' => 'مستلم جزئياً',
        'fully_received' => 'مستلم بالكامل',

        // Billing phase
        'to_bill' => 'للفوترة',
        'partially_billed' => 'مفوتر جزئياً',
        'fully_billed' => 'مفوتر بالكامل',

        // Final states
        'done' => 'مكتمل',
        'cancelled' => 'ملغي',
    ],

    'help' => [
        'po_number' => 'يتم إنشاؤه تلقائياً عند تأكيد أمر الشراء.',
        'reference' => 'رقم المرجع الخارجي أو رقم عرض أسعار المورد.',
        'exchange_rate' => 'سعر الصرف المستخدم لتحويل العملة عند إنشاء أمر الشراء.',
        'delivery_location' => 'الموقع الافتراضي حيث سيتم استلام البضائع.',
        'terms_and_conditions' => 'الشروط والأحكام لأمر الشراء هذا.',
    ],

    'billing_status' => [
        'not_billed' => 'غير مفوتر',
        'billed' => 'مفوتر',
        'multiple_bills' => ':count فواتير',
    ],
];
