<?php

return [
    // Reconciliation Types
    'type' => [
        'manual_ar_ap' => 'يدوي للذمم المدينة/الدائنة',
        'manual_ar_ap_description' => 'التسوية اليدوية لحسابات الذمم المدينة والذمم الدائنة',
        'bank_statement' => 'كشف الحساب البنكي',
        'bank_statement_description' => 'تسوية كشف الحساب البنكي مع المدفوعات',
        'manual_general' => 'يدوي عام',
        'manual_general_description' => 'تسوية يدوية عامة لقيود اليومية',
    ],

    // Company Settings
    'company' => [
        'enable_reconciliation' => 'تمكين التسوية',
        'enable_reconciliation_help' => 'تمكين وظيفة التسوية لهذه الشركة. عند التعطيل، سيتم إخفاء جميع ميزات التسوية.',
    ],

    // Account Settings
    'account' => [
        'allow_reconciliation' => 'السماح بالتسوية',
        'allow_reconciliation_help' => 'السماح باستخدام هذا الحساب في عمليات التسوية (الذمم المدينة، الدائنة، البنك).',
    ],

    // Partner Unreconciled Entries
    'partner' => [
        'unreconciled_entries_relation_manager' => [
            'title' => 'قيود غير مسواة',
            'entry_date' => 'تاريخ القيد',
            'reference' => 'المرجع',
            'account_code' => 'رمز الحساب',
            'account_name' => 'اسم الحساب',
            'description' => 'الوصف',
            'debit' => 'مدين',
            'credit' => 'دائن',
            'reconcile_selected' => 'تسوية المحدد',
            'reconcile' => 'تسوية',
            'reconcile_modal_heading' => 'تسوية قيود اليومية',
            'reconcile_modal_description' => 'سيؤدي هذا إلى إنشاء سجل تسوية يربط أسطر قيود اليومية المحددة. تأكد من أن إجمالي المدين يساوي إجمالي الدائن.',
            'reconcile_reference' => 'المرجع',
            'reconcile_description' => 'الوصف',
            'empty_state_heading' => 'لا توجد قيود غير مسواة',
            'empty_state_description' => 'تمت تسوية جميع أسطر قيود اليومية لهذا الشريك أو لا توجد قيود في الحسابات القابلة للتسوية.',
            'use_bulk_action' => 'يرجى استخدام الإجراء الجماعي لتسوية القيود.',
            'reconciliation_success' => 'تمت التسوية بنجاح',
            'reconciliation_success_body' => 'تمت تسوية :count سطر قيد يومية بنجاح. مرجع التسوية: :reference',
            'reconciliation_error' => 'خطأ في التسوية',
            'reconciliation_error_generic' => 'حدث خطأ غير متوقع أثناء التسوية. يرجى المحاولة مرة أخرى.',
        ],
    ],

    // Error Messages
    'errors' => [
        'reconciliation_disabled' => 'وظيفة التسوية معطلة لهذه الشركة.',
        'account_not_reconcilable' => 'حساب واحد أو أكثر لا يسمح بالتسوية.',
        'unbalanced_reconciliation' => 'القيود المحددة غير متوازنة. يجب أن يساوي إجمالي المدين إجمالي الدائن.',
        'partner_mismatch' => 'يجب أن تنتمي جميع القيود لنفس الشريك لتسوية الذمم المدينة والدائنة.',
        'already_reconciled' => 'واحد أو أكثر من القيود تمت تسويتها بالفعل.',
        'invalid_entries' => 'تم تقديم أسطر قيود يومية غير صالحة.',
        'unposted_entries' => 'لا يمكن تسوية قيود يومية غير مرحلة.',
    ],

    // Success Messages
    'success' => [
        'reconciliation_created' => 'تم إنشاء التسوية بنجاح.',
        'reconciliation_completed' => 'تم إكمال التسوية بنجاح.',
    ],

    // General
    'reconciliation' => 'التسوية',
    'reconciliations' => 'التسويات',
    'reconciled' => 'مسواة',
    'unreconciled' => 'غير مسواة',
    'reconciled_at' => 'تمت التسوية في',
    'reconciled_by' => 'تمت التسوية بواسطة',
    'reference' => 'المرجع',
    'description' => 'الوصف',
    'total_debits' => 'إجمالي المدين',
    'total_credits' => 'إجمالي الدائن',
    'balance' => 'الرصيد',
    'line_count' => 'عدد الأسطر',
];
