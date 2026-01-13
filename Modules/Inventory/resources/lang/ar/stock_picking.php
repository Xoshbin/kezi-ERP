<?php

return [
    'label' => 'استلام/تسليم المخزون',
    'plural_label' => 'عمليات المخزون',
    'navigation_label' => 'عمليات المخزون',

    // GRN specific
    'goods_receipt' => 'إشعار استلام البضائع',
    'goods_receipt_plural' => 'إشعارات استلام البضائع',
    'grn_number' => 'رقم إشعار الاستلام',
    'validated_at' => 'تم التحقق في',
    'validated_by' => 'تم التحقق بواسطة',

    // Types
    'types' => [
        'receipt' => 'استلام',
        'delivery' => 'تسليم',
        'internal' => 'داخلي',
    ],

    // States
    'states' => [
        'draft' => 'مسودة',
        'confirmed' => 'مؤكد',
        'assigned' => 'مسند',
        'done' => 'تم',
        'cancelled' => 'ملغى',
    ],

    // Form labels
    'reference' => 'المرجع',
    'partner' => 'الشريك',
    'origin' => 'المصدر',
    'purchase_order' => 'أمر الشراء',
    'scheduled_date' => 'تاريخ الجدول',
    'completed_at' => 'اكتمل في',

    // Actions
    'validate' => 'تحقق',
    'validate_receipt' => 'التحقق من الاستلام',
    'cancel' => 'إلغاء',
    'create_backorder' => 'إنشاء طلب مؤجل',

    // Messages
    'receipt_validated' => 'تم التحقق من إشعار استلام البضائع بنجاح.',
    'cannot_validate' => 'لا يمكن التحقق من عملية المخزون هذه.',
    'already_done' => 'تم إكمال عملية المخزون هذه بالفعل.',
    'already_cancelled' => 'تم إلغاء عملية المخزون هذه بالفعل.',

    // Partial receipt
    'partial_receipt' => 'استلام جزئي',
    'backorder_created' => 'تم إنشاء طلب مؤجل للكمية المتبقية.',
    'remaining_quantity' => 'الكمية المتبقية',
    'quantity_to_receive' => 'الكمية للاستلام',
    'planned_quantity' => 'الكمية المخططة',
    'received_quantity' => 'الكمية المستلمة',

    // View Stock Picking
    'moves' => 'التحركات',
    'total_moves' => 'إجمالي التحركات',
    'move_details' => 'تفاصيل التحرك',
    'stock_moves' => 'تحركات المخزون',
    'product' => 'المنتج',
    'actual_fulfilled_quantity' => 'الكمية الفعلية / المنفذة',
    'assigned_lots' => 'الدفعات المسندة',
    'validate_done' => 'تحقق (تم)',
    'operations' => 'العمليات',

    'fields' => [
        'type' => 'النوع',
        'state' => 'الحالة',
    ],
];
