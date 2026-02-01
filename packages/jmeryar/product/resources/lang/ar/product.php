<?php

return [
    // Labels
    'label' => 'منتج',
    'plural_label' => 'منتجات',

    // Basic Information
    'basic_information' => 'المعلومات الأساسية',
    'basic_information_description' => 'أدخل تفاصيل المنتج الأساسية بما في ذلك الاسم ورمز المنتج والنوع.',
    'company' => 'شركة',
    'name' => 'اسم',
    'sku' => 'رمز المنتج',
    'sku_copied' => 'تم نسخ رمز المنتج إلى الحافظة!',
    'description' => 'وصف',
    'type' => 'نوع',

    // Pricing Information
    'pricing_information' => 'معلومات التسعير',
    'pricing_information_description' => 'حدد سعر الوحدة الافتراضي لهذا المنتج.',
    'unit_price' => 'سعر الوحدة',

    // Accounting Configuration
    'accounting_configuration' => 'إعداد المحاسبة',
    'accounting_configuration_description' => 'قم بتكوين حسابات الإيراد والمصروف الافتراضية لهذا المنتج.',
    'income_account' => 'حساب الإيراد',
    'expense_account' => 'حساب المصروف',
    'purchase_tax' => 'ضريبة المشتريات',

    // Inventory Management
    'inventory_management' => 'إدارة المخزون',
    'inventory_management_description' => 'قم بتكوين طريقة تقييم المخزون والمحاسبة للمنتجات القابلة للتخزين.',
    'inventory_valuation_method' => 'طريقة التقييم',
    'inventory_valuation_method_help' => 'اختر كيفية حساب تكاليف المخزون (FIFO، LIFO، AVCO، أو السعر المعياري).',
    'average_cost' => 'متوسط التكلفة',
    'average_cost_help' => 'متوسط التكلفة الحالية لكل وحدة (محسوبة تلقائياً).',
    'default_inventory_account' => 'حساب المخزون',
    'default_cogs_account' => 'حساب تكلفة البضاعة المباعة',
    'default_stock_input_account' => 'حساب إدخال المخزون',
    'default_price_difference_account' => 'حساب فرق السعر',
    'lot_tracking_enabled' => 'تفعيل تتبع الدفعات',
    'lot_tracking_enabled_help' => 'تفعيل تتبع الدفعات/المجموعات لهذا المنتج لتتبع الأرقام التسلسلية أو الدفعات أو تواريخ انتهاء الصلاحية.',

    // Stock Information
    'stock_moves' => 'حركات المخزون',
    'inventory_cost_layers' => 'طبقات التكلفة',
    'quantity_on_hand' => 'الكمية المتوفرة',

    // Status
    'status' => 'حالة',
    'status_description' => 'تحكم في ما إذا كان هذا المنتج نشطاً ومتاحاً للاستخدام.',
    'is_active' => 'نشط',
    'is_active_help' => 'المنتجات غير النشطة لا يمكن استخدامها في معاملات جديدة.',

    // Filters
    'all_products' => 'جميع المنتجات',
    'active_products' => 'النشطة فقط',
    'inactive_products' => 'غير النشطة فقط',

    // Legacy fields (for backward compatibility)
    'company_id' => 'شركة',
    'income_account_id' => 'حساب الإيراد',
    'expense_account_id' => 'حساب المصروف',
    'sku_label' => 'رمز المنتج',
    'sku_column' => 'رمز المنتج',
    'created_at' => 'تاريخ الإنشاء',
    'updated_at' => 'تاريخ التحديث',
    'deleted_at' => 'تاريخ الحذف',
];
