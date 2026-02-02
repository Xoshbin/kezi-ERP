<?php

return [
    // Labels
    'label' => 'دفتر يومية',
    'plural_label' => 'دفاتر اليومية',

    // Basic fields
    'company' => 'شركة',
    'name' => 'اسم',
    'type' => 'نوع',
    'short_code' => 'رمز مختصر',
    'currency' => 'عملة',
    'created_at' => 'تاريخ الإنشاء',
    'updated_at' => 'تاريخ التحديث',

    // Sections
    'details' => 'تفاصيل دفتر اليومية',
    'details_description' => 'إعداد الاسم والنوع والرمز المختصر والعملة لهذا الدفتر',
    'default_accounts' => 'الحسابات الافتراضية',
    'default_accounts_description' => 'حسابا المدين والدائن الافتراضيان المستخدمان بواسطة هذا الدفتر',

    // JournalResource.php
    'default_debit_account' => 'حساب المدين الافتراضي',
    'default_debit_account_helper' => 'لدفاتر البنك/النقد، هذا هو الحساب البنكي المستخدم للمدفوعات.',
    'default_credit_account' => 'حساب الدائن الافتراضي',
    'default_credit_account_helper' => 'لدفاتر البنك/النقد، هذا هو الحساب البنكي المستخدم للمدفوعات.',
    'default_debit_account_short' => 'حساب المدين الافتراضي',
    'default_credit_account_short' => 'حساب الدائن الافتراضي',

    // JournalEntriesRelationManager.php
    'entry_date' => 'تاريخ القيد',
    'reference' => 'مرجع',
    'description' => 'وصف',
    'is_posted' => 'مرحل',
    'journal_entries' => 'قيود اليومية',
    'fields' => [
        'bank_account' => 'الحساب البنكي',
    ],
];
