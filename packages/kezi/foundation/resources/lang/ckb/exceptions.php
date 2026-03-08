<?php

return [
    'currency' => [
        'provider_not_registered' => "دابینکەر ':identifier' تۆمارنەکراوە",
        'not_found' => 'دراوی :currency نەدۆزرایەوە',
        'no_exchange_rate' => 'هیچ ڕێژەیەکی گۆڕینەوە نەدۆزرایەوە بۆ دراوی :currency لە بەرواری :date',
        'in_use' => 'ناتوانرێت دراوێک بسڕدرێتەوە کە لەوبەکاربردنەدایە.',
    ],
    'partner' => [
        'in_use' => 'ناتوانرێت هاوبەشێک بسڕدرێتەوە کە لەوبەکاربردنەدایە.',
        'company_or_currency_not_found' => 'کۆمپانیای هاوبەش یان دراو نەدۆزرایەوە',
    ],
    'cast' => [
        'invalid_money_value' => 'بڕگەیەکی نەگونجاوە، دەبێت ژمارە یان جۆری دراو (Money) بێت.',
        'empty_original_currency' => 'کۆمەڵەی دراوی ڕەسەن بەتاڵە',
        'empty_foreign_currency' => 'کۆمەڵەی دراوی بیانی بەتاڵە',
        'empty_currency' => 'کۆمەڵەی دراو بەتاڵە',
        'missing_internal_currency' => 'مۆدێلەکە پێکهاتەی original_currency_id یان foreign_currency_id ی تێدانییە.',
        'resolve_base_currency' => 'نەتوانرا دراوی بنەڕەتی بۆ مۆدێلی :class دیاری بکرێت. تکایە دڵنیابە کە پەیوەندی کۆمپانیا دروستە.',
        'resolve_document_currency' => 'نەتوانرا دراوی بەڵگەنامە بۆ مۆدێلی :class دیاری بکرێت. تکایە دڵنیابە پەیوەندی بەڵگەنامەی سەرەکی دروستە.',
        'invoice_currency_not_found' => 'دراوی وەسڵ نەدۆزرایەوە',
        'vendor_bill_currency_not_found' => 'دراوی وەسڵی فرۆشیار نەدۆزرایەوە',
        'adjustment_document_currency_not_found' => 'دراوی بەڵگەنامەی ڕێکخستن نەدۆزرایەوە',
        'payment_currency_not_found' => 'دراوی پارەدان نەدۆزرایەوە',
        'bank_statement_currency_not_found' => 'دراوی هەژماری بانکی نەدۆزرایەوە',
        'loan_currency_not_found' => 'دراوی قەرز نەدۆزرایەوە',
        'purchase_order_currency_not_found' => 'دراوی داواکاری کڕین نەدۆزرایەوە',
        'sales_order_currency_not_found' => 'دراوی داواکاری فرۆشتن نەدۆزرایەوە',
        'quote_currency_not_found' => 'دراوی نرخدان نەدۆزرایەوە',
        'installmentable_currency_not_found' => 'دراوی قیست نەدۆزرایەوە',
    ],
];
