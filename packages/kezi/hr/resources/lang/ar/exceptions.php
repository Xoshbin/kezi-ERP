<?php

return [
    'leave_request' => [
        'only_pending_can_be_approved' => 'يمكن فقط الموافقة على طلبات الإجازة المعلقة.',
        'only_pending_can_be_rejected' => 'يمكن فقط رفض طلبات الإجازة المعلقة.',
        'only_pending_approved_can_be_cancelled' => 'يمكن فقط إلغاء طلبات الإجازة المعلقة أو المعتمدة.',
        'insufficient_balance' => 'رصيد الإجازة غير كافٍ. الرصيد المتاح: :available يوم.',
        'minimum_notice_required' => 'يجب تقديم إشعار مسبق قبل :days يوم على الأقل.',
        'maximum_consecutive_days_exceeded' => 'الحد الأقصى للأيام المتتالية المسموح بها: :days يوم.',
        'overlapping_request' => 'يتداخل طلب الإجازة مع إجازة موجودة.',
        'refresh_failed_after_creation' => 'فشل تحديث طلب الإجازة بعد إنشائه.',
    ],
    'position' => [
        'salary_currency_not_found' => 'عملة راتب الوظيفة غير موجودة.',
        'max_salary_not_found' => 'الحد الأقصى لراتب الوظيفة غير موجود.',
        'from' => 'من :amount',
        'up_to' => 'يصل إلى :amount',
    ],
    'casts' => [
        'salary_currency_resolution_failed' => 'تعذر تحديد عملة الراتب للنموذج :model. يرجى التأكد من أن النموذج يحتوي على معرف عملة صالح أو علاقة بالشركة.',
        'payroll_currency_resolution_failed' => 'تعذر تحديد عملة الراتب للنموذج :model. يرجى التأكد من أن النموذج يحتوي على علاقة رواتب صالحة.',
        'collection_empty' => 'مجموعة عملات الراتب فارغة.',
    ],
    'payroll' => [
        'active_contract_required' => 'الموظف ليس لديه عقد نشط.',
        'only_draft_can_be_approved' => 'يمكن فقط الموافقة على مسودات كشوف المرتبات.',
        'already_paid' => 'تم دفع كشف المرتبات بالفعل.',
        'only_processed_can_be_paid' => 'يمكن فقط دفع كشوف المرتبات التي تمت معالجتها.',
        'no_bank_journal_found' => 'لم يتم العثور على دفتر يومية مصرفي افتراضي للشركة. <a href=":link" class="underline font-bold">تكوين في إعدادات الشركة</a>.',
        'salary_payable_account_not_configured' => 'لم يتم تكوين حساب مستحق الراتب الافتراضي للشركة. <a href=":link" class="underline font-bold">تكوين في إعدادات الشركة</a>.',
        'refresh_failed_after_creation' => 'فشل تحديث كشف المرتبات بعد إنشائه.',
    ],
    'attendance' => [
        'already_clocked_in' => 'لقد قام الموظف بالفعل بتسجيل الدخول اليوم.',
        'not_clocked_in' => 'الموظف لم يسجل الدخول اليوم.',
        'already_clocked_out' => 'لقد قام الموظف بالفعل بتسجيل الخروج اليوم.',
        'refresh_failed_after_clock_out' => 'فشل تحديث بيانات الحضور بعد تسجيل الخروج.',
        'break_already_started' => 'تم بدء الاستراحة بالفعل.',
        'refresh_failed_after_break_start' => 'فشل تحديث بيانات الحضور بعد بدء الاستراحة.',
        'break_not_started' => 'لم يتم بدء الاستراحة.',
        'break_already_ended' => 'تم إنهاء الاستراحة بالفعل.',
        'refresh_failed_after_break_end' => 'فشل تحديث بيانات الحضور بعد إنهاء الاستراحة.',
    ],
    'employee' => [
        'refresh_failed_after_creation' => 'فشل تحديث بيانات الموظف بعد إنشائه.',
        'only_terminated_can_be_reactivated' => 'يمكن فقط إعادة تفعيل الموظفين الذين تم إنهاء خدمتهم.',
    ],
    'cash_advance' => [
        'only_draft_can_be_submitted' => 'يمكن فقط تقديم السلف النقدية المسودة.',
        'only_pending_settlement_can_be_settled' => 'يمكن فقط تسوية السلف النقدية التي تنتظر التسوية.',
        'receivable_account_not_configured' => 'لم يتم تكوين حساب مدين سلف الموظفين للشركة. <a href=":link" class="underline font-bold">تكوين في إعدادات الشركة</a>.',
        'no_journal_found' => 'لم يتم العور على أي دفتر يومية للشركة.',
        'bank_account_required_for_return' => 'حساب البنك مطلوب لإعادة النقد.',
        'bank_account_required_for_reimbursement' => 'حساب البنك مطلوب للتعويض.',
    ],
    'contract' => [
        'refresh_failed_after_creation' => 'فشل تحديث العقد بعد إنشائه.',
        'currency_not_found' => 'العملة غير موجودة.',
        'company_not_found' => 'الشركة غير موجودة.',
    ],
    'common' => [
        'user_not_authenticated' => 'يجب تسجيل الدخول للقيام بهذا الإجراء.',
        'field_name_required' => 'اسم الحقل مطلوب.',
    ],
];
