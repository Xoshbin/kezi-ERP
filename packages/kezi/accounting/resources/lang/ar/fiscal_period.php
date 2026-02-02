<?php

return [
    'model_label' => 'الفترة المالية',
    'plural_model_label' => 'الفترات المالية',
    'field_name' => 'الاسم',
    'field_start_date' => 'تاريخ البدء',
    'field_end_date' => 'تاريخ الانتهاء',
    'field_state' => 'الحالة',
    'action_close' => 'إغلاق الفترة',
    'action_reopen' => 'إعادة فتح الفترة',
    'close_confirmation_title' => 'إغلاق الفترة المالية؟',
    'close_confirmation_desc' => 'سيتم قفل جميع المعاملات في هذه الفترة. متابعة؟',
    'closed_successfully' => 'تم إغلاق الفترة المالية بنجاح.',
    'close_failed' => 'فشل إغلاق الفترة المالية.',
    'reopen_confirmation_title' => 'إعادة فتح الفترة المالية؟',
    'reopen_confirmation_desc' => 'سيتم فتح قفل المعاملات في هذه الفترة. متابعة؟',
    'reopened_successfully' => 'تم إعادة فتح الفترة المالية بنجاح.',
    'reopen_failed' => 'فشل إعادة فتح الفترة المالية.',
    'validation' => [
        'not_open' => 'الفترة المالية ليست في حالة \'مفتوحة\'.',
        'not_closed' => 'الفترة المالية ليست في حالة \'مغلقة\'.',
        'year_closed' => 'لا يمكن إغلاق الفترة: السنة المالية مغلقة بالفعل.',
        'year_closed_reopen' => 'لا يمكن إعادة فتح الفترة: السنة المالية مغلقة.',
        'draft_entries' => 'يوجد :count قيود مسودة في هذه الفترة. يرجى ترحيلها أو حذفها.',
    ],
];
