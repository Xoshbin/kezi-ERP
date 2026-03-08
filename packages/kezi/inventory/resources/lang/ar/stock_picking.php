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

    'sections' => [
        'actual_quantities' => 'الكميات الفعلية',
        'confirm_quantities_description' => 'تأكيد الكميات الفعلية التي تم استلامها/تسليمها لكل تحرك.',
    ],

    'placeholders' => [
        'no_lots_assigned' => 'لم يتم إسناد دفعات',
    ],

    'modal' => [
        'assign_picking' => 'إسناد العملية',
        'assign' => 'إسناد',
        'reserve_stock_description' => 'حجز المخزون وتخصيص دفعات محددة لهذه العملية.',
        'stock_moves' => 'تحركات المخزون',
        'review_moves_description' => 'مراجعة وإسناد الدفعات لكل تحرك مخزني في هذه العملية.',
        'product' => 'المنتج',
        'from' => 'من',
        'to' => 'إلى',
        'lot_assignments' => 'إسناد الدفعات',
        'lot' => 'الدفعة',
        'quantity' => 'الكمية',
        'add_lot' => 'إضافة دفعة',
    ],

    'notifications' => [
        'error' => 'خطأ',
        'validated' => 'تم التحقق من العملية',
        'assigned' => 'تم إسناد العملية',
        'assigned_body' => 'تم إسناد العملية بنجاح. تم حجز المخزون وتخصيص الدفعات.',
        'failed_to_assign' => 'فشل إسناد العملية: :error',
        'no_lines_to_validate' => 'لا توجد بنود للتحقق منها.',
        'confirmed' => 'تم تأكيد العملية',
        'confirmed_body' => 'تم تأكيد العملية بنجاح. جميع تحركات المخزون مؤكدة الآن.',
        'cancelled' => 'تم إلغاء العملية',
        'cancelled_body' => 'تم إلغاء العملية بنجاح. تم تحرير جميع الحجوزات.',
        'confirm_description' => 'هل أنت متأكد من رغبتك في تأكيد هذه العملية؟ سيؤدي ذلك إلى تأكيد جميع تحركات المخزون المرتبطة بها.',
        'cancel_description' => 'هل أنت متأكد من رغبتك في إلغاء هذه العملية؟ سيؤدي ذلك إلى إلغاء جميع تحركات المخزون المرتبطة بها وتحرير الحجوزات.',
        'failed_to_confirm' => 'فشل تأكيد العملية: :error',
        'failed_to_cancel' => 'فشل إلغاء العملية: :error',
    ],
];
