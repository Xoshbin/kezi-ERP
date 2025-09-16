<?php

return [
    'label' => 'تعريف الحقل المخصص',
    'plural_label' => 'تعريفات الحقول المخصصة',
    'navigation_label' => 'الحقول المخصصة',
    'section_title' => 'الحقول المخصصة',
    
    'fields' => [
        'model_type' => 'نوع النموذج',
        'name' => 'الاسم',
        'description' => 'الوصف',
        'is_active' => 'نشط',
        'field_definitions' => 'تعريفات الحقول',
        'field_key' => 'مفتاح الحقل',
        'field_label' => 'تسمية الحقل',
        'field_type' => 'نوع الحقل',
        'field_required' => 'مطلوب',
        'field_options' => 'الخيارات',
        'field_validation_rules' => 'قواعد التحقق',
        'field_help_text' => 'نص المساعدة',
        'field_order' => 'الترتيب',
        'option_value' => 'القيمة',
        'option_label' => 'التسمية',
    ],
    
    'sections' => [
        'basic_information' => 'المعلومات الأساسية',
        'basic_information_description' => 'تكوين الإعدادات الأساسية لتعريف الحقل المخصص هذا.',
        'field_definitions' => 'تعريفات الحقول',
        'field_definitions_description' => 'تحديد الحقول المخصصة التي ستكون متاحة لهذا النموذج.',
        'field_configuration' => 'تكوين الحقل',
        'field_options' => 'خيارات الحقل',
        'field_validation' => 'التحقق والمساعدة',
    ],
    
    'actions' => [
        'add_field' => 'إضافة حقل',
        'remove_field' => 'إزالة حقل',
        'add_option' => 'إضافة خيار',
        'remove_option' => 'إزالة خيار',
        'move_up' => 'تحريك لأعلى',
        'move_down' => 'تحريك لأسفل',
    ],
    
    'placeholders' => [
        'field_key' => 'مثال: جهة_اتصال_طوارئ',
        'field_label' => 'مثال: جهة اتصال الطوارئ',
        'field_help_text' => 'معلومات إضافية لمساعدة المستخدمين في ملء هذا الحقل',
        'validation_rules' => 'مثال: max:255, email',
        'option_value' => 'مثال: خيار1',
        'option_label' => 'مثال: الخيار 1',
    ],
    
    'help' => [
        'model_type' => 'اختر نوع النموذج الذي سيستخدم هذه الحقول المخصصة.',
        'field_key' => 'معرف فريد لهذا الحقل. استخدم الأحرف الصغيرة والأرقام والشرطات السفلية فقط.',
        'field_type' => 'نوع حقل الإدخال الذي سيتم عرضه للمستخدمين.',
        'field_required' => 'ما إذا كان يجب على المستخدمين ملء هذا الحقل.',
        'field_options' => 'للحقول المنسدلة، حدد الخيارات المتاحة.',
        'validation_rules' => 'قواعد التحقق الإضافية لـ Laravel (مفصولة بفواصل).',
        'field_order' => 'الترتيب الذي سيظهر به هذا الحقل في النماذج.',
    ],
    
    'validation' => [
        'field_key_required' => 'مفتاح الحقل مطلوب.',
        'field_key_unique' => 'يجب أن يكون مفتاح الحقل فريداً ضمن هذا التعريف.',
        'field_key_format' => 'يجب أن يحتوي مفتاح الحقل على أحرف صغيرة وأرقام وشرطات سفلية فقط.',
        'field_label_required' => 'تسمية الحقل مطلوبة.',
        'field_type_required' => 'نوع الحقل مطلوب.',
        'select_options_required' => 'يجب أن تحتوي حقول الاختيار على خيار واحد على الأقل.',
        'option_value_required' => 'قيمة الخيار مطلوبة.',
        'option_label_required' => 'تسمية الخيار مطلوبة.',
    ],
    
    'messages' => [
        'no_fields_defined' => 'لم يتم تعريف أي حقول مخصصة لهذا النموذج بعد.',
        'definition_saved' => 'تم حفظ تعريف الحقل المخصص بنجاح.',
        'definition_deleted' => 'تم حذف تعريف الحقل المخصص بنجاح.',
        'field_added' => 'تم إضافة الحقل بنجاح.',
        'field_removed' => 'تم إزالة الحقل بنجاح.',
        'invalid_model_type' => 'تم اختيار نوع نموذج غير صحيح.',
    ],
    
    'model_types' => [
        'App\\Models\\Partner' => 'الشركاء',
        'App\\Models\\Product' => 'المنتجات',
        'App\\Models\\Employee' => 'الموظفون',
        'App\\Models\\Department' => 'الأقسام',
        'App\\Models\\Position' => 'المناصب',
        'App\\Models\\Asset' => 'الأصول',
        'App\\Models\\Project' => 'المشاريع',
    ],
];
