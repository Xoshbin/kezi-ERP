<?php

return [
    'currency' => [
        'provider_not_registered' => "المزود ':identifier' غير مسجل",
        'not_found' => 'العملة :currency غير موجودة',
        'no_exchange_rate' => 'لم يتم العثور على سعر صرف لعملة :currency في تاريخ :date',
        'in_use' => 'لا يمكن حذف عملة قيد الاستخدام.',
    ],
    'partner' => [
        'in_use' => 'لا يمكن حذف شريك قيد الاستخدام.',
        'company_or_currency_not_found' => 'لم يتم العثور على الشريك أو العملة',
    ],
    'cast' => [
        'invalid_money_value' => 'قيمة غير صالحة، يجب أن تكون رقمية أو نسخة من نوع العملة (Money).',
        'empty_original_currency' => 'مجموعة العملة الأصلية فارغة',
        'empty_foreign_currency' => 'مجموعة العملة الأجنبية فارغة',
        'empty_currency' => 'مجموعة العملة فارغة',
        'missing_internal_currency' => 'النموذج لا يحتوي على original_currency_id أو foreign_currency_id.',
        'resolve_base_currency' => 'تعذر حل العملة الأساسية للنموذج :class. يرجى التأكد من أن النموذج يحتوي على علاقة شركة صحيحة.',
        'resolve_document_currency' => 'تعذر حل عملة المستند للنموذج :class. يرجى التأكد من أن النموذج لديه علاقة صحيحة بالمستند الأساسي.',
        'invoice_currency_not_found' => 'لم يتم العثور على عملة الفاتورة',
        'vendor_bill_currency_not_found' => 'لم يتم العثور على عملة فاتورة المورد',
        'adjustment_document_currency_not_found' => 'لم يتم العثور على عملة مستند التسوية',
        'payment_currency_not_found' => 'لم يتم العثور على عملة الدفع',
        'bank_statement_currency_not_found' => 'لم يتم العثور على عملة كشف الحساب البنكي',
        'loan_currency_not_found' => 'لم يتم العثور على عملة القرض',
        'purchase_order_currency_not_found' => 'لم يتم العثور على عملة أمر الشراء',
        'sales_order_currency_not_found' => 'لم يتم العثور على عملة أمر البيع',
        'quote_currency_not_found' => 'لم يتم العثور على عملة عرض السعر',
        'installmentable_currency_not_found' => 'لم يتم العثور على العملة الخاصة بالأقساط',
    ],
];
