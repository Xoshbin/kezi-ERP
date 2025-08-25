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

    'payment_purpose' => [
        'settlement' => 'تسوية',
        'loan' => 'قرض',
        'capital_injection' => 'حقن رأس المال',
        'expense_claim' => 'مطالبة مصروفات',
        'tax_payment' => 'دفع ضريبة',
        'asset_purchase' => 'شراء أصول',
        'payroll' => 'كشف الراتب',
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
];
