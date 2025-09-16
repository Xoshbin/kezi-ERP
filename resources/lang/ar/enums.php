<?php

// resources/lang/ar/enums.php

return [
    'account_type' => [
        'asset' => 'أصل',
        'liability' => 'التزام',
        'equity' => 'حقوق الملكية',
        'income' => 'إيراد',
        'expense' => 'مصروف',
    ],

    'journal_entry_state' => [
        'draft' => 'مسودة',
        'posted' => 'مرحل',
        'reversed' => 'معكوس',
    ],

    'journal_type' => [
        'sale' => 'مبيعات',
        'purchase' => 'مشتريات',
        'bank' => 'بنك',
        'cash' => 'نقد',
        'inventory' => 'مخزون',
        'miscellaneous' => 'متنوع',
    ],

    'lock_date_type' => [
        'tax_return_date' => 'قفل الإقرار الضريبي',
        'everything_date' => 'قفل جميع المستخدمين',
        'hard_lock' => 'قفل صارم (غير قابل للتغيير)',
    ],

    'asset_status' => [
        'draft' => 'مسودة',
        'confirmed' => 'مؤكد',
        'depreciating' => 'يتم إهلاكه',
        'fully_depreciated' => 'مهلك بالكامل',
        'sold' => 'مباع',
    ],

    'depreciation_entry_status' => [
        'draft' => 'مسودة',
        'posted' => 'مرحل',
    ],

    'depreciation_method' => [
        'straight_line' => 'القسط الثابت',
        'declining' => 'الرصيد المتناقص',
    ],

    'stock_location_type' => [
        'internal' => 'داخلي',
        'customer' => 'عميل',
        'vendor' => 'مورد',
        'inventory_adjustment' => 'تسوية المخزون',
    ],

    'stock_move_status' => [
        'draft' => 'مسودة',
        'confirmed' => 'مؤكد',
        'done' => 'منجز',
        'cancelled' => 'ملغي',
    ],

    'stock_move_type' => [
        'incoming' => 'وارد',
        'outgoing' => 'صادر',
        'internal_transfer' => 'نقل داخلي',
        'adjustment' => 'تسوية',
    ],

    'valuation_method' => [
        'fifo' => 'الوارد أولاً صادر أولاً (FIFO)',
        'lifo' => 'الوارد أخيراً صادر أولاً (LIFO)',
        'avco' => 'متوسط التكلفة (AVCO)',
        'standard_price' => 'السعر المعياري',
    ],

    'partner_type' => [
        'customer' => 'عميل',
        'vendor' => 'مورد',
        'both' => 'كلاهما',
    ],

    'product_type' => [
        'product' => 'منتج',
        'storable' => 'منتج قابل للتخزين',
        'consumable' => 'مستهلك',
        'service' => 'خدمة',
    ],

    'vendor_bill_status' => [
        'draft' => 'مسودة',
        'posted' => 'مرحل',
        'cancelled' => 'ملغي',
        'paid' => 'مدفوع',
    ],

    'adjustment_document_type' => [
        'credit_note' => 'إشعار دائن',
        'debit_note' => 'إشعار مدين',
        'miscellaneous' => 'متنوع',
    ],

    'adjustment_document_status' => [
        'draft' => 'مسودة',
        'posted' => 'مرحل',
        'cancelled' => 'ملغي',
    ],

    'invoice_status' => [
        'draft' => 'مسودة',
        'posted' => 'مرحل',
        'paid' => 'مدفوع',
        'cancelled' => 'ملغي',
    ],

    'payment_type' => [
        'inbound' => 'وارد',
        'outbound' => 'صادر',
    ],

    'payment_status' => [
        'draft' => 'مسودة',
        'confirmed' => 'مؤكد',
        'reconciled' => 'مطابق',
        'canceled' => 'ملغي',
    ],

    'payment_state' => [
        'not_paid' => 'غير مدفوع',
        'partially_paid' => 'مدفوع جزئياً',
        'paid' => 'مدفوع',
    ],

    'payment_method' => [
        'manual' => 'يدوي',
        'check' => 'شيك',
        'bank_transfer' => 'تحويل بنكي',
        'credit_card' => 'بطاقة ائتمان',
        'debit_card' => 'بطاقة خصم',
        'cash' => 'نقد',
        'wire_transfer' => 'تحويل سلكي',
        'ach' => 'ACH',
        'sepa' => 'SEPA',
        'online_payment' => 'دفع إلكتروني',
    ],

    'payroll_status' => [
        'draft' => 'مسودة',
        'processed' => 'معالج',
        'paid' => 'مدفوع',
        'cancelled' => 'ملغي',
    ],

    'pay_frequency' => [
        'monthly' => 'شهري',
        'bi_weekly' => 'كل أسبوعين',
        'weekly' => 'أسبوعي',
    ],

    'tax_type' => [
        'sales' => 'مبيعات',
        'purchase' => 'مشتريات',
        'both' => 'كلاهما',
    ],

    'budget_type' => [
        'analytic' => 'تحليلي',
        'financial' => 'مالي',
    ],

    'budget_status' => [
        'draft' => 'مسودة',
        'finalized' => 'نهائي',
    ],

    'custom_field_type' => [
        'text' => 'نص',
        'textarea' => 'منطقة نص',
        'number' => 'رقم',
        'boolean' => 'نعم/لا',
        'date' => 'تاريخ',
        'select' => 'اختيار',
    ],
];
