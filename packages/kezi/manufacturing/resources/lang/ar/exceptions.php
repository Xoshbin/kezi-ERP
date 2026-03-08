<?php

return [
    'order' => [
        'confirm_draft_only' => 'يمكن تأكيد أوامر التصنيع في حالة المسودة فقط.',
        'consume_in_progress_only' => 'يمكن فقط لأوامر التصنيع قيد التنفيذ استهلاك المكونات.',
        'user_required_for_consumption' => 'مطلوب مستخدم لإجراء استهلاك المكونات.',
        'consumption_accounts_not_configured' => 'لم يتم تكوين حسابات التصنيع (المواد الخام، تحت التشغيل، يومية التصنيع) للشركة :company.',
        'manufacturing_accounts_not_configured' => 'لم يتم تكوين حسابات التصنيع (المنتجات النهائية، تحت التشغيل، يومية التصنيع) للشركة :company.',
        'overhead_account_not_configured' => 'لم يتم تكوين حساب المصاريف الإدارية للتصنيع للشركة :company.',
        'no_scrap_location' => 'لم يتم العثور على موقع للخردة للشركة :company. يرجى تكوين واحد.',
        'produce_in_progress_only' => 'يمكن فقط لأوامر التصنيع قيد التنفيذ إنتاج منتجات نهائية.',
        'user_required_for_production' => 'مطلوب مستخدم لإجراء التحقق من الإنتاج.',
        'no_lines_to_process' => 'أمر التصنيع :order لا يحتوي على بنود لمعالجتها.',
        'start_confirmed_only' => 'يمكن فقط بدء أوامر التصنيع المؤكدة.',
    ],
    'bom' => [
        'self_reference' => 'لا يمكن أن يكون المنتج مكونًا لنفسه في قائمة المواد.',
        'circular_dependency' => 'تم اكتشاف اعتمادية دائرية في قائمة المواد.',
        'max_explosion_depth' => 'تم الوصول إلى أقصى عمق لتفجير قائمة المواد (اعتمادية دائرية؟).',
    ],
    'actions' => [
        'edit_order' => 'تعديل الأمر',
        'view_order' => 'عرض الأمر',
        'view_stock_locations' => 'عرض مواقع المخزون',
        'view_accounting_settings' => 'عرض إعدادات الحسابات',
    ],
    'notifications' => [
        'confirm_failed' => 'فشل التأكيد',
        'start_failed' => 'فشل بدء الإنتاج',
        'complete_failed' => 'فشل إكمال الإنتاج',
        'cancel_failed' => 'فشل الإلغاء',
        'scrap_failed' => 'فشل التخريد',
        'order_confirmed' => 'تم تأكيد أمر التصنيع وهو جاهز للإنتاج.',
        'production_started' => 'تم استهلاك المكونات وبدأ الإنتاج.',
        'production_completed' => 'تمت إضافة المنتجات النهائية إلى المخزون.',
    ],
];
